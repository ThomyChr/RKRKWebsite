<?php
/**
 * Planung eines Prüfungstags: Halbtage, parallele Stationsgruppen, Warteliste.
 */
if (function_exists("opcache_invalidate")) {
	opcache_invalidate(__FILE__, true);
	opcache_invalidate(__DIR__ . "/rkrk_assess_pruefungstag.inc.php", true);
	opcache_invalidate(__DIR__ . "/rkrk_assess_access_policy.inc.php", true);
	opcache_invalidate(__DIR__ . "/rkrk_assess_common.php", true);
}
clearstatcache(true);
if (function_exists("ob_get_level") && ob_get_level() === 0) {
	ob_start();
}
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
include_once __DIR__ . "/rkrk_assess_trainingstatus.inc.php";
include_once __DIR__ . "/rkrk_assess_pruefungstag.inc.php";
include_once __DIR__ . "/rkrk_assess_name_assign.inc.php";

if (function_exists("ProtectFromAccess")) {
	CheckIPandExit($folderoffset);
}
GrantAccess($folderoffset);

$shareToken = strtolower(trim((string) ($_GET["einsicht"] ?? "")));
$instrToken = strtolower(trim((string) ($_GET["instruktion"] ?? "")));
$ptCfg = rkrk_pt_config_load();
$shareDay = ($shareToken !== "") ? rkrk_pt_day_by_share($shareToken, $ptCfg) : null;
$instrDay = ($instrToken !== "") ? rkrk_pt_day_by_instr($instrToken, $ptCfg) : null;
$isInstrShare = is_array($instrDay);
if ($isInstrShare) {
	$shareDay = $instrDay;
}
$isShareView = is_array($shareDay);
$showStaffChapters = $isInstrShare || !$isShareView;
$showStaffPlan = $showStaffChapters;
if ($instrToken !== "" && !$isInstrShare) {
	while (function_exists("ob_get_level") && ob_get_level() > 0) {
		@ob_end_clean();
	}
	http_response_code(404);
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\"><title>Prüfungstag</title></head><body><p>Dieser Instruktorenlink ist nicht freigegeben.</p></body></html>";
	exit;
}
if ($shareToken !== "" && !$isShareView) {
	while (function_exists("ob_get_level") && ob_get_level() > 0) {
		@ob_end_clean();
	}
	http_response_code(404);
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\"><title>Prüfungstag</title></head><body><p>Dieser Teilnehmerlink ist nicht freigegeben.</p></body></html>";
	exit;
}

if (empty($LoggedOn) && !$isShareView) {
	if (function_exists("WriteToLog")) {
		WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", $folderoffset);
	}
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	headerpage(function_exists("rkrk_assess_copyright_holder") ? rkrk_assess_copyright_holder() : "Drohnenrehkitzrettung", false, $folderoffset);
	if (function_exists("RegisterForm")) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

$currentAccessLevel = "";
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
	$currentAccessLevel = strtolower(trim((string) $_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo["Login"]["Level"])) {
	$currentAccessLevel = strtolower(trim((string) $InputUserInfo["Login"]["Level"]));
}
$isGuestRequest = (isset($_GET["GA"]) && strtolower(trim((string) $_GET["GA"])) === "gast");
$canEdit = (!$isShareView && !$isGuestRequest && $currentAccessLevel !== "gast");

$trCfg = rkrk_tr_config_load();
$lists = isset($trCfg["lists"]) && is_array($trCfg["lists"]) ? $trCfg["lists"] : array();

if ($_SERVER["REQUEST_METHOD"] === "POST" && $canEdit) {
	$action = trim((string) ($_POST["pt_action"] ?? ""));
	$days = isset($ptCfg["days"]) && is_array($ptCfg["days"]) ? $ptCfg["days"] : array();
	$ok = false;
	$redirDay = trim((string) ($_POST["day"] ?? ""));
	if ($action === "create") {
		$used = array();
		foreach ($days as $d) {
			if (is_array($d)) {
				$used[(string) ($d["id"] ?? "")] = true;
			}
		}
		$fresh = rkrk_pt_empty_day(trim((string) ($_POST["name"] ?? "")), $used);
		$fresh["list_id"] = trim((string) ($_POST["list_id"] ?? ""));
		if (isset($_POST["parallel"])) {
			$fresh["parallel"] = (int) $_POST["parallel"];
		}
		if (isset($_POST["start_vormittag"])) {
			$fresh["groups"] = array();
			$fresh["places"] = array();
			foreach (rkrk_pt_halfday_defs() as $hd) {
				$hid = $hd["id"];
				$fresh["halfdays"][$hid] = isset($_POST["half_" . $hid]);
				$fresh["starts"][$hid] = (string) ($_POST["start_" . $hid] ?? "");
				$fresh["groups"][$hid] = (int) ($_POST["groups_" . $hid] ?? 1);
				$fresh["places"][$hid] = (int) ($_POST["places_" . $hid] ?? 3);
			}
		}
		$fresh = rkrk_pt_normalize_day($fresh, $used);
		$days[] = $fresh;
		$ok = rkrk_pt_config_save(array("days" => $days));
		$redirDay = (string) $fresh["id"];
	} elseif ($action === "copy") {
		$source = null;
		$used = array();
		foreach ($days as $d) {
			if (!is_array($d)) {
				continue;
			}
			$used[(string) ($d["id"] ?? "")] = true;
			if ((string) ($d["id"] ?? "") === $redirDay) {
				$source = $d;
			}
		}
		if (is_array($source)) {
			$name = trim((string) ($_POST["name"] ?? ""));
			if ($name === "") {
				$name = "Kopie von " . trim((string) ($source["name"] ?? "Prüfungstag"));
			}
			$fresh = $source;
			$fresh["name"] = $name;
			$fresh["id"] = "";
			$fresh["share"] = "";
			$fresh["share_instr"] = "";
			$fresh["roster_share"] = false;
			$fresh["roster_pdf"] = false;
			$fresh["roster_instr_teil_link"] = false;
			$fresh["roster_instr_instr_link"] = false;
			$fresh["roster_teil_teil_link"] = false;
			$fresh["roster_teil_instr_link"] = false;
			$fresh["roster_instr_pdf"] = false;
			$fresh["roster_teil_pdf"] = false;
			$fresh = rkrk_pt_normalize_day($fresh, $used);
			$fresh["marks"] = array();
			$srcImg = rkrk_pt_place_image_fs($source);
			$place = isset($fresh["place"]) && is_array($fresh["place"]) ? $fresh["place"] : rkrk_pt_place_defaults();
			$place["image"] = "";
			if ($srcImg !== "") {
				$dir = rkrk_pt_place_dir($fresh);
				$ext = strtolower(pathinfo($srcImg, PATHINFO_EXTENSION));
				if ($ext !== "jpg") {
					$ext = "png";
				}
				$destName = (string) $fresh["id"] . "." . $ext;
				if ($dir !== "" && preg_match("/^[a-z0-9-]{1,64}\\.(png|jpg)$/", $destName) === 1) {
					if (!is_dir($dir)) {
						@mkdir($dir, 0777, true);
					}
					if (@copy($srcImg, $dir . DIRECTORY_SEPARATOR . $destName)) {
						$place["image"] = $destName;
					}
				}
			}
			$fresh["place"] = $place;
			rkrk_pt_copy_dir(rkrk_pt_chapter_dir($source), rkrk_pt_chapter_dir($fresh));
			$days[] = $fresh;
			$ok = rkrk_pt_config_save(array("days" => $days));
			$redirDay = (string) $fresh["id"];
		}
	} elseif ($action === "delete") {
		$keep = array();
		foreach ($days as $d) {
			if (!is_array($d) || (string) ($d["id"] ?? "") === $redirDay) {
				if (is_array($d)) {
					rkrk_pt_rm_dir(rkrk_pt_day_dir((string) ($d["id"] ?? "")));
				}
				continue;
			}
			$keep[] = $d;
		}
		$ok = rkrk_pt_config_save(array("days" => $keep));
		$redirDay = "";
	} elseif ($action === "share_on" || $action === "share_off" || $action === "share_instr_on" || $action === "share_instr_off" || $action === "staff" || $action === "roster_pub" || $action === "place" || $action === "chapters") {
		foreach ($days as $i => $d) {
			if (!is_array($d) || (string) ($d["id"] ?? "") !== $redirDay) {
				continue;
			}
			if ($action === "share_on") {
				$cur = (string) ($d["share"] ?? "");
				if ($cur === "") {
					$days[$i]["share"] = rkrk_pt_new_share_token();
				}
			} elseif ($action === "share_off") {
				$days[$i]["share"] = "";
			} elseif ($action === "share_instr_on") {
				$curInstr = (string) ($d["share_instr"] ?? "");
				if ($curInstr === "") {
					$days[$i]["share_instr"] = rkrk_pt_new_share_token();
				}
			} elseif ($action === "share_instr_off") {
				$days[$i]["share_instr"] = "";
			} elseif ($action === "roster_pub") {
				$days[$i]["roster_instr_teil_link"] = isset($_POST["roster_instr_teil_link"]);
				$days[$i]["roster_instr_instr_link"] = isset($_POST["roster_instr_instr_link"]);
				$days[$i]["roster_instr_pdf"] = isset($_POST["roster_instr_pdf"]);
				$days[$i]["roster_teil_teil_link"] = isset($_POST["roster_teil_teil_link"]);
				$days[$i]["roster_teil_instr_link"] = isset($_POST["roster_teil_instr_link"]);
				$days[$i]["roster_teil_pdf"] = isset($_POST["roster_teil_pdf"]);
			} elseif ($action === "place") {
				$prev = isset($d["place"]) && is_array($d["place"]) ? $d["place"] : array();
				$placeShow = array();
				foreach (array("maps", "swisstopo", "overlay") as $placeKey) {
					$placeShow[$placeKey] = array(
						"teil" => isset($_POST["place_show_" . $placeKey . "_teil"]),
						"instr" => isset($_POST["place_show_" . $placeKey . "_instr"]),
					);
				}
				$place = rkrk_pt_normalize_place(array(
					"ort" => (string) ($_POST["place_ort"] ?? ""),
					"lat" => (string) ($_POST["place_lat"] ?? ""),
					"lng" => (string) ($_POST["place_lng"] ?? ""),
					"maps" => (string) ($_POST["place_maps"] ?? ""),
					"note" => (string) ($_POST["place_note"] ?? ""),
					"swisstopo" => (string) ($_POST["place_swisstopo"] ?? ""),
					"edit" => (string) ($_POST["place_edit"] ?? ""),
					"overlay" => (string) ($_POST["place_overlay"] ?? ""),
					"show" => $placeShow,
					"image" => (string) ($prev["image"] ?? ""),
				));
				if (!empty($_POST["place_clear"])) {
					$gone = rkrk_pt_place_image_fs($d);
					if ($gone !== "") {
						@unlink($gone);
					}
					$place["image"] = "";
				}
				$file = isset($_FILES["place_image"]) && is_array($_FILES["place_image"]) ? $_FILES["place_image"] : null;
				if (is_array($file) && (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
					$tmp = (string) ($file["tmp_name"] ?? "");
					$size = (int) ($file["size"] ?? 0);
					$info = ($tmp !== "" && is_uploaded_file($tmp)) ? @getimagesize($tmp) : false;
					$type = is_array($info) ? (int) ($info[2] ?? 0) : 0;
					$ext = "";
					if ($type === IMAGETYPE_PNG) {
						$ext = "png";
					} elseif ($type === IMAGETYPE_JPEG) {
						$ext = "jpg";
					}
					$dir = rkrk_pt_place_dir($d);
					$name = ($ext !== "" && preg_match("/^[a-z0-9-]{1,64}$/", $redirDay) === 1) ? $redirDay . "." . $ext : "";
					if ($ext !== "" && $size > 0 && $size <= 8 * 1024 * 1024 && $dir !== "" && $name !== "") {
						if (!is_dir($dir)) {
							@mkdir($dir, 0777, true);
						}
						$dest = $dir . DIRECTORY_SEPARATOR . $name;
						$prevFs = rkrk_pt_place_image_fs($d);
						if ($prevFs !== "" && $prevFs !== $dest) {
							@unlink($prevFs);
						}
						if (@move_uploaded_file($tmp, $dest)) {
							$place["image"] = $name;
						}
					}
				}
				$days[$i]["place"] = $place;
			} elseif ($action === "chapters") {
				$days[$i]["chapters"] = rkrk_pt_chapters_from_request($d);
			} else {
				$halfId = trim((string) ($_POST["half_id"] ?? ""));
				$laneNo = (int) ($_POST["lane"] ?? 0);
				$roles = array();
				$postedRoles = isset($_POST["slot"]) && is_array($_POST["slot"]) ? $_POST["slot"] : array();
				foreach (rkrk_pt_station_roles() as $role) {
					$roleId = $role["id"];
					$src = isset($postedRoles[$roleId]) && is_array($postedRoles[$roleId]) ? $postedRoles[$roleId] : array();
					$roles[$roleId] = array(
						trim((string) ($src[0] ?? "")),
						trim((string) ($src[1] ?? "")),
						trim((string) ($src[2] ?? "")),
					);
				}
				$beforeStaff = isset($d["staff"]) && is_array($d["staff"]) ? $d["staff"] : array();
				$staff = $beforeStaff;
				$staff[$halfId][(string) $laneNo] = array(
					"roles" => $roles,
					"note" => (string) ($_POST["note"] ?? ""),
				);
				$leadPosted = isset($_POST["lead"]) && is_array($_POST["lead"]);
				if ($leadPosted) {
					$postedLead = $_POST["lead"];
					$staff[$halfId]["lead"] = array(
						"tageschef" => rkrk_pt_clean_pid((string) ($postedLead["tageschef"] ?? "")),
						"techn" => rkrk_pt_clean_pid((string) ($postedLead["techn"] ?? "")),
					);
				}
				$days[$i]["staff"] = $staff;
				rkrk_pt_mark_staff_diff($days[$i], $beforeStaff, $staff, $halfId, $laneNo, $leadPosted);
			}
			$ok = rkrk_pt_config_save(array("days" => $days));
			break;
		}
	} elseif ($action === "settings" || $action === "move" || $action === "reorder") {
		foreach ($days as $i => $d) {
			if (!is_array($d) || (string) ($d["id"] ?? "") !== $redirDay) {
				continue;
			}
			if ($action === "settings") {
				$oldId = (string) ($d["id"] ?? "");
				$oldListId = trim((string) ($d["list_id"] ?? ""));
				$posted = $d;
				$posted["name"] = trim((string) ($_POST["name"] ?? $d["name"] ?? ""));
				$posted["list_id"] = trim((string) ($_POST["list_id"] ?? ""));
				$taken = array();
				foreach ($days as $other) {
					if (!is_array($other)) {
						continue;
					}
					$oid = (string) ($other["id"] ?? "");
					if ($oid !== "" && $oid !== $oldId) {
						$taken[$oid] = true;
					}
				}
				$wantId = rkrk_pt_unique_day_id($posted["name"], $oldId, $taken);
				if ($wantId !== $oldId) {
					rkrk_pt_rename_day_storage($oldId, $wantId, $posted);
					$redirDay = $wantId;
				}
				$posted["halfdays"] = array();
				$posted["starts"] = array();
				$posted["groups"] = array();
				$posted["places"] = array();
				foreach (rkrk_pt_halfday_defs() as $hd) {
					$hid = $hd["id"];
					$posted["halfdays"][$hid] = isset($_POST["half_" . $hid]);
					$posted["starts"][$hid] = (string) ($_POST["start_" . $hid] ?? "");
					$posted["groups"][$hid] = (int) ($_POST["groups_" . $hid] ?? rkrk_pt_groups_of($d, $hid));
					$posted["places"][$hid] = (int) ($_POST["places_" . $hid] ?? rkrk_pt_places_of($d, $hid));
				}
				$days[$i] = rkrk_pt_normalize_day($posted, array());
				$days[$i]["id"] = (string) ($posted["id"] ?? $d["id"]);
				$newListId = trim((string) ($days[$i]["list_id"] ?? ""));
				if ($newListId !== $oldListId) {
					rkrk_pt_reset_for_new_list($days[$i], rkrk_tr_list_by_id($newListId, $trCfg));
				} else {
					$days[$i]["order"] = isset($d["order"]) && is_array($d["order"]) ? $d["order"] : array();
				}
			} elseif ($action === "reorder") {
				$list = rkrk_tr_list_by_id((string) ($d["list_id"] ?? ""), $trCfg);
				$pack = rkrk_pt_people_for_list($list);
				$off = rkrk_pt_withdrawn_set($d);
				$known = array();
				foreach ($pack["people"] as $p) {
					$id = (string) $p["id"];
					if (!isset($off[$id])) {
						$known[$id] = true;
					}
				}
				$postedIds = explode(",", (string) ($_POST["order_ids"] ?? ""));
				$order = array();
				$seen = array();
				foreach ($postedIds as $rawId) {
					$id = trim((string) $rawId);
					if ($id === "") {
						$order[] = "";
						continue;
					}
					if (isset($known[$id]) && !isset($seen[$id])) {
						$order[] = $id;
						$seen[$id] = true;
					}
				}
				foreach ($pack["people"] as $p) {
					$id = (string) $p["id"];
					if (!isset($seen[$id]) && isset($known[$id])) {
						$order[] = $id;
						$seen[$id] = true;
					}
				}
				$activePeople = array();
				foreach ($pack["people"] as $p) {
					if (isset($known[(string) $p["id"]])) {
						$activePeople[] = $p;
					}
				}
				$beforeMap = rkrk_pt_placement_map($d, $activePeople);
				$days[$i]["order"] = $order;
				rkrk_pt_mark_placement_diff($days[$i], $beforeMap, rkrk_pt_placement_map($days[$i], $activePeople));
			} else {
				$list = rkrk_tr_list_by_id((string) ($d["list_id"] ?? ""), $trCfg);
				$pack = rkrk_pt_people_for_list($list);
				$ordered = rkrk_pt_apply_order($pack["people"], isset($d["order"]) && is_array($d["order"]) ? $d["order"] : array());
				$pid = trim((string) ($_POST["pilot_id"] ?? ""));
				$dir = (string) ($_POST["dir"] ?? "");
				$idx = -1;
				foreach ($ordered as $n => $p) {
					if ((string) $p["id"] === $pid) {
						$idx = $n;
						break;
					}
				}
				if ($idx >= 0) {
					$swap = $dir === "up" ? $idx - 1 : ($dir === "down" ? $idx + 1 : -1);
					if ($swap >= 0 && $swap < count($ordered)) {
						$tmp = $ordered[$idx];
						$ordered[$idx] = $ordered[$swap];
						$ordered[$swap] = $tmp;
					}
				}
				$order = array();
				foreach ($ordered as $p) {
					$order[] = (string) $p["id"];
				}
				$activePeople = array();
				$offMove = rkrk_pt_withdrawn_set($d);
				foreach ($pack["people"] as $p) {
					if (!isset($offMove[(string) $p["id"]])) {
						$activePeople[] = $p;
					}
				}
				$beforeMap = rkrk_pt_placement_map($d, $activePeople);
				$days[$i]["order"] = $order;
				rkrk_pt_mark_placement_diff($days[$i], $beforeMap, rkrk_pt_placement_map($days[$i], $activePeople));
			}
			$ok = rkrk_pt_config_save(array("days" => $days));
			break;
		}
	} elseif ($action === "clear_marks") {
		foreach ($days as $i => $d) {
			if (!is_array($d) || (string) ($d["id"] ?? "") !== $redirDay) {
				continue;
			}
			$days[$i]["marks"] = array();
			$ok = rkrk_pt_config_save(array("days" => $days));
			break;
		}
	} elseif ($action === "withdraw" || $action === "enroll" || $action === "assign") {
		if ($action === "assign") {
			$ok = rkrk_na_assign(
				trim((string) ($_POST["kind"] ?? "")),
				trim((string) ($_POST["file"] ?? "")),
				trim((string) ($_POST["person_id"] ?? ""))
			);
		} else {
			$pid = rkrk_pt_clean_pid((string) ($_POST["pilot_id"] ?? ""));
			foreach ($days as $i => $d) {
				if (!is_array($d) || (string) ($d["id"] ?? "") !== $redirDay || $pid === "") {
					continue;
				}
				$list = rkrk_tr_list_by_id((string) ($d["list_id"] ?? ""), $trCfg);
				$pack = rkrk_pt_people_for_list($list);
				$byId = array();
				foreach ($pack["people"] as $p) {
					$byId[(string) $p["id"]] = $p;
				}
				$off = rkrk_pt_withdrawn_set($d);
				$listIdNow = (string) ($d["list_id"] ?? "");
				if ($action === "withdraw") {
					if (!isset($byId[$pid]) || isset($off[$pid])) {
						break;
					}
					rkrk_pt_day_mark_withdrawn($d, $pid, (string) ($byId[$pid]["name"] ?? $pid));
					rkrk_pt_list_set_member($listIdNow, $pid, false);
				} else {
					if (!isset($off[$pid])) {
						break;
					}
					$nameBack = isset($byId[$pid]) ? (string) $byId[$pid]["name"] : rkrk_pt_person_label($pid);
					rkrk_pt_day_mark_enrolled($d, $pid, $nameBack);
					rkrk_pt_list_set_member($listIdNow, $pid, true);
				}
				$days[$i] = $d;
				$ok = rkrk_pt_config_save(array("days" => $days));
				break;
			}
		}
	}
	$q = $ok ? array("ok" => "1") : array("err" => "1");
	if ($action === "chapters" && $ok) {
		$q["info"] = "1";
	}
	if ($redirDay !== "") {
		$q["day"] = $redirDay;
	}
	while (function_exists("ob_get_level") && ob_get_level() > 0) {
		@ob_end_clean();
	}
	header("Location: " . rkrk_pt_page_href($q), true, 303);
	exit;
}

$days = isset($ptCfg["days"]) && is_array($ptCfg["days"]) ? $ptCfg["days"] : array();
$dayId = trim((string) ($_GET["day"] ?? ""));
$day = $isShareView ? $shareDay : ($dayId !== "" ? rkrk_pt_day_by_id($dayId, $ptCfg) : null);
if (!$isShareView && $day === null && $days !== array() && is_array($days[0])) {
	$day = $days[0];
	$dayId = (string) ($day["id"] ?? "");
}
if (is_array($day)) {
	$dayId = (string) ($day["id"] ?? $dayId);
}
$staffCatalog = rkrk_pt_staff_catalog();

$list = null;
$people = array();
$peopleSource = array();
$skipped = 0;
$plan = array("blocks" => array(), "wait" => array(), "capacity" => 0, "parallel" => 1, "halfdays" => 0);
if (is_array($day)) {
	$list = rkrk_tr_list_by_id((string) ($day["list_id"] ?? ""), $trCfg);
	$pack = rkrk_pt_people_for_list($list);
	$peopleSource = $pack["people"];
	$people = rkrk_pt_people_active($peopleSource, $day);
	$skipped = (int) $pack["skipped"];
	$plan = rkrk_pt_plan($day, $people);
}

$planningHref = function_exists("rkrk_assess_append_setting_href")
	? rkrk_assess_append_setting_href("rkrk_assess_admin.php?page=planning")
	: "rkrk_assess_admin.php?page=planning";
$listHref = function_exists("rkrk_tr_page_href") ? rkrk_tr_page_href() : "rkrk_assess_trainingstatus.php";
$pdfHref = "";
if (is_array($day)) {
	if ($isInstrShare && (string) ($day["share_instr"] ?? "") !== "") {
		$pdfHref = rkrk_pt_instr_url((string) $day["share_instr"]) . "&pdf=1";
	} elseif ($isShareView && (string) ($day["share"] ?? "") !== "") {
		$pdfHref = rkrk_pt_share_url((string) $day["share"]) . "&pdf=1";
	} else {
		$pdfHref = rkrk_pt_page_href(array("day" => $dayId, "pdf" => "1"));
	}
}
if (isset($_GET["bild"]) && is_array($day)) {
	$bildFs = rkrk_pt_place_image_fs($day);
	if ($bildFs === "") {
		header("HTTP/1.1 404 Not Found");
		header("Content-Type: text/plain; charset=UTF-8");
		echo "Kein Bild.";
		exit;
	}
	header("Content-Type: " . rkrk_pt_place_image_mime($bildFs));
	header("Content-Length: " . (string) filesize($bildFs));
	header("Cache-Control: private, max-age=300");
	readfile($bildFs);
	exit;
}
if (isset($_GET["datei"]) && is_array($day)) {
	while (function_exists("ob_get_level") && ob_get_level() > 0) {
		@ob_end_clean();
	}
	$dateiName = rkrk_pt_chapter_file_name((string) $_GET["datei"]);
	$dateiFs = ($dateiName !== "" && rkrk_pt_chapter_file_visible($day, $dateiName, $showStaffChapters))
		? rkrk_pt_chapter_file_fs($day, $dateiName) : "";
	if ($dateiFs === "") {
		header("HTTP/1.1 404 Not Found");
		header("Content-Type: text/plain; charset=UTF-8");
		echo "Keine Datei.";
		exit;
	}
	$dateiMime = rkrk_pt_chapter_file_mime($dateiName);
	header("Content-Type: " . $dateiMime);
	header("Content-Length: " . (string) filesize($dateiFs));
	header("X-Content-Type-Options: nosniff");
	header("Cache-Control: private, max-age=300");
	if (strpos($dateiMime, "image/") !== 0) {
		header("Content-Disposition: attachment; filename=\"" . $dateiName . "\"");
	}
	readfile($dateiFs);
	exit;
}
if (isset($_GET["pdf"]) && is_array($day)) {
	while (function_exists("ob_get_level") && ob_get_level() > 0) {
		@ob_end_clean();
	}
	$listName = is_array($list) ? (string) ($list["name"] ?? "") : "";
	$pdfAudience = $isInstrShare ? "instr" : ($isShareView ? "teil" : "admin");
	rkrk_pt_send_pdf($day, $plan, $staffCatalog, $listName, $showStaffChapters, $pdfAudience);
	exit;
}

rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
headerpage("Prüfungstag", false, $folderoffset);
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (!$isShareView && function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}
if (!$isShareView && function_exists("rkrk_assess_portal_nav_echo")) {
	rkrk_assess_portal_nav_echo(array(
		"is_admin" => isset($_GET["ADMIN"]) || !empty($onAdmin),
		"is_instr" => !empty($onInstr),
	));
}

$waitCount = count($plan["wait"]);
$seatCount = count($people);
?>
<style>
.pt-wrap{max-width:1100px;margin:12px auto 28px;padding:0 12px;color:#1f2937;}
.pt-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:10px;}
.pt-head h1{margin:0;font-size:22px;}
.pt-today{margin:2px 0 0;font-size:15px;font-weight:700;color:#1f2937;}
.pt-chapters h2{margin:0 0 6px;font-size:16px;}
.pt-chapter{margin:0 0 14px;}
.pt-chapter p,.pt-chapter li{font-size:14px;line-height:1.35;}
.pt-chapter a{color:#1d4ed8;}
.pt-chapter b,.pt-chapter strong{font-weight:700;}
.pt-chapter p{margin:0 0 8px;}
.pt-chapter ul,.pt-chapter ol{margin:0 0 8px;padding-left:18px;}
.pt-chapter li{margin:0;padding:0;}
.pt-stafftag{margin-left:6px;font-size:11px;font-weight:700;color:#92400e;}
.pt-chapter-row{display:flex;gap:14px;align-items:flex-start;}
.pt-chapter-copy{flex:1;min-width:0;}
.pt-chapter-img{display:block;height:auto;border-radius:6px;cursor:zoom-in;}
.pt-figure{margin:0;}
.pt-figcap{margin:4px 0 0;font-size:12px;font-style:italic;line-height:1.3;color:#374151;}
.pt-img-full .pt-chapter-row{flex-direction:column;}
.pt-img-full .pt-figure,.pt-img-full .pt-chapter-img{width:100%;max-width:100%;}
.pt-img-left .pt-figure,.pt-img-right .pt-figure{width:42%;max-width:460px;flex:0 0 auto;}
.pt-img-left .pt-chapter-img,.pt-img-right .pt-chapter-img{width:100%;max-width:100%;}
.pt-img-right .pt-chapter-row{flex-direction:row-reverse;}
.pt-chapter-extra{margin:8px 0 0;}
.pt-chapter-extra summary{cursor:pointer;font-weight:700;color:#1d4ed8;}
.pt-chapter-extra-body{margin-top:8px;}
.pt-files{margin:8px 0 0;padding-left:18px;}
.pt-files a{font-weight:700;}
.pt-ch-edit{border:1px solid #d7e0d4;border-radius:8px;padding:10px;margin:0 0 10px;background:#fff;}
.pt-ch-edit.pt-ch-dragging{opacity:.55;}
.pt-ch-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 8px;}
.pt-ch-handle{cursor:grab;user-select:none;font-weight:700;color:#64748b;padding:4px 6px;border:1px solid #d7e0d4;border-radius:6px;background:#f8fafc;}
.pt-ch-pos{min-width:1.6em;font-weight:700;}
.pt-ch-bar input[type="text"]{flex:1;min-width:12rem;}
.pt-ch-edit textarea{width:100%;box-sizing:border-box;min-height:7em;font:inherit;}
.pt-ch-edit label{display:block;font-size:12px;font-weight:700;margin:8px 0 3px;}
.pt-ch-meta{display:flex;gap:12px;flex-wrap:wrap;align-items:end;}
.pt-ch-meta select,.pt-ch-meta input[type="text"],.pt-ch-meta input[type="url"]{min-width:12rem;}
.pt-ch-del{margin-left:auto;}
@media (max-width:700px){
.pt-img-left .pt-chapter-row,.pt-img-right .pt-chapter-row{flex-direction:column;}
.pt-img-left .pt-figure,.pt-img-right .pt-figure,.pt-img-left .pt-chapter-img,.pt-img-right .pt-chapter-img{width:100%;max-width:100%;}
}
.pt-lead{margin:4px 0 0;color:#64748b;font-size:13px;}
.pt-links{display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
.pt-links a{display:inline-flex;align-items:center;gap:5px;font-size:13px;}
.pt-jsonrev{font-size:11px;font-style:italic;color:#64748b;align-self:center;}
.pt-links svg{width:15px;height:15px;flex:0 0 auto;}
.pt-msg{margin:8px 0;padding:8px 10px;border-radius:6px;font-size:13px;}
.pt-msg-ok{background:#ecfdf3;color:#166534;}
.pt-msg-err{background:#fef2f2;color:#991b1b;}
.pt-off{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;margin-left:6px;padding:0;border:1px solid #fecdd3;border-radius:999px;background:#fff1f2;color:#9f1239;cursor:pointer;vertical-align:middle;}
.pt-off svg,.pt-on svg{width:14px;height:14px;display:block;}
.pt-on{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;padding:0;border:1px solid #bbf7d0;border-radius:999px;background:#f0fdf4;color:#166534;cursor:pointer;vertical-align:middle;}
.pt-assign{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;}
.pt-assign select{max-width:320px;}
.pt-log{width:100%;border-collapse:collapse;font-size:13px;}
.pt-log td,.pt-log th{text-align:left;padding:4px 8px 4px 0;border-bottom:1px solid #e5e7eb;vertical-align:middle;}
.pt-days{display:flex;gap:6px;flex-wrap:wrap;margin:10px 0;}
.pt-days a,.pt-btn{display:inline-block;padding:6px 10px;border:1px solid #c5d0c3;border-radius:999px;background:#f3f6f2;color:#1f2937;text-decoration:none;font-size:13px;font-weight:600;}
.pt-days a.on{background:#48673a;color:#fff;border-color:#48673a;}
.pt-card{border:1px solid #d7e0d4;border-radius:10px;padding:12px;margin:0 0 12px;background:#fff;}
.pt-card h2{margin:0 0 8px;font-size:16px;}
.pt-fold{padding:0;}
.pt-fold > summary{cursor:pointer;padding:12px 14px;font-size:16px;font-weight:700;list-style:none;display:flex;gap:8px;align-items:center;}
.pt-fold > summary::-webkit-details-marker{display:none;}
.pt-fold > summary::before{content:"";width:.42em;height:.42em;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(-45deg);display:inline-block;opacity:.55;flex:0 0 auto;}
.pt-fold[open] > summary::before{transform:rotate(45deg);margin-top:-3px;}
.pt-fold > .pt-fold-body{padding:0 14px 12px;}
.pt-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end;}
.pt-grid label{display:block;font-size:12px;font-weight:700;margin-bottom:3px;}
.pt-grid input,.pt-grid select{width:100%;box-sizing:border-box;padding:6px 8px;}
.pt-grid input[type="checkbox"]{width:auto;padding:0;margin:0;}
.pt-grid .pt-clear{display:flex;align-items:center;}
.pt-grid .pt-clear label{display:flex;align-items:center;gap:8px;margin:0;font-size:13px;font-weight:600;}
.pt-half{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.pt-half input[type="text"]{width:5.2em;}
.pt-half .pt-num{width:4.2em;}
.pt-half span{font-size:12px;font-weight:700;color:#475569;}
.pt-halfday{grid-column:1 / -1;}
.pt-actions{display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;}
.pt-cap{margin:0 0 12px;padding:8px 10px;background:#f8faf8;border:1px solid #d7e0d4;border-radius:8px;font-size:14px;}
.pt-place-ort{margin:0 0 6px;font-size:15px;}
.pt-place-ort a,.pt-place-links a{margin-left:8px;}
.pt-place-row{display:flex;gap:16px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;}
.pt-place-row > img{display:block;width:min(100%,520px);max-width:100%;height:auto;margin:0;border:1px solid #111;flex:1 1 280px;}
.pt-place-links{margin:0;font-size:13px;}
.pt-qrs{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;margin:0;justify-content:flex-end;}
.pt-share-main{flex:1;min-width:0;}
.pt-qr-item{display:flex;flex-direction:column;align-items:center;gap:3px;}
.pt-qr-open{border:0;padding:0;background:#fff;cursor:zoom-in;line-height:0;}
.pt-qrs a{margin:0;color:#1f2937;font-size:12px;text-decoration:none;}
.pt-qrs svg{width:120px;height:120px;display:block;}
.pt-share .pt-qrs svg{width:64px;height:64px;}
.pt-share-line{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:8px;}
.pt-share-label{flex:0 0 7.5rem;font-size:13px;font-weight:700;}
.pt-share-main{flex:1 1 18rem;min-width:0;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.pt-share-main input{flex:1 1 14rem;width:auto;max-width:36rem;min-width:12rem;}
.pt-share-main .pt-actions{margin-top:0;}
.pt-share .pt-note{margin:0;}
.pt-place .pt-qr-item{max-width:140px;text-align:center;}
.pt-place .pt-qrs a{text-align:center;line-height:1.2;}
.pt-grid .pt-link-flags{display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;}
.pt-grid .pt-link-flags label{display:flex;gap:4px;align-items:center;margin:0;font-size:12px;font-weight:600;}
.pt-qr-pop{border:0;border-radius:14px;padding:22px 22px 16px;max-width:92vw;background:#fff;}
.pt-qr-pop::backdrop{background:rgba(15,23,42,.45);}
.pt-qr-pop-x{position:absolute;top:8px;right:8px;border:0;background:transparent;font-size:22px;line-height:1;cursor:pointer;color:#334155;}
.pt-qr-pop-body{display:flex;justify-content:center;}
.pt-qr-pop svg{width:min(78vw,460px);height:auto;display:block;}
.pt-qr-pop p{margin:8px 0 0;text-align:center;font-size:14px;font-weight:700;}
.pt-img-pop{border:0;padding:8px;background:transparent;max-width:96vw;max-height:96vh;}
.pt-img-pop::backdrop{background:rgba(15,23,42,.82);}
.pt-img-pop img{display:block;max-width:min(1200px,94vw);max-height:88vh;width:auto;height:auto;margin:0 auto;border-radius:4px;}
.pt-img-pop .pt-qr-pop-x{color:#f8fafc;}
.pt-img-pop p{margin:8px 0 0;text-align:center;font-size:14px;font-style:italic;color:#f8fafc;}
.pt-img-pop p:empty{display:none;}
.pt-lanes{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;}
.pt-table{width:100%;border-collapse:collapse;font-size:13px;}
.pt-table th,.pt-table td{border-bottom:1px solid #e5e7eb;padding:4px 6px;text-align:left;vertical-align:top;}
.pt-table th{font-size:11px;color:#64748b;font-weight:700;}
.pt-plan{margin:22px 0 2px;font-size:20px;font-weight:700;color:#111;}
.pt-sec{margin:18px 0 8px;padding:0 0 3px;border-bottom:1px solid #111;font-size:16px;font-weight:650;}
.pt-matrix{width:100%;border-collapse:collapse;font-size:12px;line-height:1.3;background:#fff;margin:0 0 8px;}
.pt-matrix th,.pt-matrix td{border:none;border-bottom:1px solid #e5e7eb;padding:3px 6px;text-align:left;vertical-align:middle;}
.pt-matrix thead th{font-weight:700;background:#fff;color:#111;font-size:12px;}
.pt-matrix tbody tr:nth-child(odd) td{background:#f2f2f2;}
.pt-matrix select{width:100%;border:0;background:transparent;font:inherit;color:#111;padding:0;}
.pt-matrix td.pt-chg,.pt-matrix tbody tr:nth-child(odd) td.pt-chg,.pt-table tr.pt-chg td,.pt-seatrows tr.pt-chg td{background:#fff4cc;}
.pt-matrix td.pt-chg select{background:#fff4cc;}
.pt-seatrows tr.pt-chg:hover td{background:#fff4cc;}
.pt-chg-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0 0 12px;padding:8px 10px;background:#fff8dc;border:1px solid #f3e3a1;border-radius:8px;font-size:13px;}
.pt-chg-bar form{margin:0;}
.pt-free{color:#94a3b8;}
.pt-move{display:inline-flex;gap:2px;}
.pt-move button{padding:0 6px;line-height:1.4;font-size:12px;}
.pt-seats{list-style:none;margin:6px 0 0;padding:0;max-width:36rem;}
.pt-seats li{display:flex;gap:8px;align-items:baseline;padding:1px 4px;border-radius:4px;font-size:13px;line-height:1.35;}
.pt-seats li[data-pt-pid]{cursor:grab;}
.pt-seats li[data-pt-pid]:hover{background:#f1f5f9;}
.pt-seats .pt-nr{flex:0 0 2.4em;font-weight:700;color:#334155;}
.pt-seats .pt-who{flex:1 1 auto;min-width:0;}
.pt-seats .pt-drone{flex:0 1 auto;color:#64748b;font-size:12px;white-space:nowrap;}
.pt-grip{color:#94a3b8;letter-spacing:-2px;user-select:none;}
.pt-seats li.pt-dragging{opacity:.4;}
.pt-seats li.pt-drop{box-shadow:inset 0 2px 0 #48673a;}
.pt-seatrows tr[data-pt-pid]{cursor:grab;}
.pt-seatrows tr[data-pt-pid]:hover td{background:#f8fafc;}
.pt-seatrows tr.pt-chg td,.pt-seatrows tr.pt-chg:hover td,.pt-seatrows tr.pt-chg[data-pt-pid]:hover td{background:#fff4cc;}
.pt-seatrows tr.pt-dragging{opacity:.4;}
.pt-seatrows tr.pt-drop td{box-shadow:inset 0 2px 0 #48673a;}
.pt-seatrows tr.pt-drop-after td{box-shadow:inset 0 -2px 0 #48673a;}
.pt-seatrows td{padding:2px 6px;}
.pt-seatrows tr.pt-wait-ready td{border-bottom:2px solid #111;}
.pt-seatrows tr.pt-wait-cut td{border-bottom:none;padding:3px 6px 8px;color:#334155;font-size:12px;font-weight:650;}
.pt-rot{margin-top:8px;}
.pt-note{color:#64748b;font-size:12px;margin:6px 0 0;}
.pt-split{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin-bottom:10px;}
.pt-staff select{width:100%;max-width:11rem;font-size:12px;}
.pt-staff textarea{width:100%;box-sizing:border-box;min-height:3.2em;font:inherit;}
.pt-share input{font-size:12px;}
.pt-roster-wrap{overflow-x:auto;margin:0 0 8px;}
.pt-mailbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 12px;}
.pt-mailbar span{font-size:13px;font-weight:700;}
.pt-mail{padding:3px 8px;font-size:12px;}
.pt-mailhead{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 6px;}
.pt-mailhead h3{margin:0;font-size:14px;}
.pt-roster{font-size:11px;}
.pt-roster th,.pt-roster td{white-space:nowrap;padding:1px 4px;line-height:1.25;}
.pt-rot{font-size:12px;}
.pt-rot th,.pt-rot td{padding:1px 4px;line-height:1.25;vertical-align:top;}
.pt-pilot{display:block;white-space:nowrap;}
.pt-ok{color:#166534;font-weight:700;}
.pt-bad{color:#991b1b;font-weight:700;}
.pt-open{color:#92400e;font-weight:700;}
.pt-miss{color:#94a3b8;font-weight:700;}
.pt-c{text-align:center;}
.pt-mark{display:inline-flex;vertical-align:middle;}
.pt-ico{width:16px;height:16px;display:block;}
.pt-pub{display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin:0 0 12px;font-size:13px;}
.pt-pub label{display:flex;gap:6px;align-items:center;font-weight:600;}
.pt-pub-set{display:inline-flex;gap:10px;align-items:center;flex-wrap:wrap;}
.pt-pub-set b{font-weight:700;}
.pt-seatwide{font-size:11px;}
.pt-seatwide th,.pt-seatwide td{white-space:nowrap;padding:2px 4px;}
.pt-roster h3{margin:0 0 4px;font-size:13px;}
</style>
<div class="pt-wrap">
	<div class="pt-head">
		<div>
			<h1>Prüfungstag<?php if ($isShareView && is_array($day)): ?> — <?php echo rkrk_pt_h((string) ($day["name"] ?? "")); ?><?php endif; ?></h1>
			<?php
				$examLabel = is_array($day) ? rkrk_pt_exam_label($day) : "";
				$revLabel = is_array($day) ? rkrk_pt_revision_label($day) : "";
			?>
			<?php if ($examLabel !== ""): ?><p class="pt-today"><?php echo rkrk_pt_h($examLabel); ?></p><?php endif; ?>
			<p class="pt-lead"><?php if ($isInstrShare): ?>Einsicht für Instruktoren. Die zusätzlichen Kapitel sind sichtbar, die Bearbeitung bleibt geschlossen.<?php elseif ($isShareView): ?>Nur Einsicht. Zeiten, Plätze und der Ablauf sind zur Ansicht freigegeben.<?php else: ?>Neun Plätze pro Stationsgruppe. Material, Flug und Theorie laufen parallel, jeder Pilot durchläuft jeden Teil einmal. Die Uhrzeiten sind Richtwerte und erzeugen keine Einträge in der Planungsübersicht.<?php endif; ?></p>
		</div>
		<div class="pt-links">
			<?php if (!$isShareView): ?>
			<a href="<?php echo rkrk_pt_h($planningHref); ?>"><svg viewBox="0 0 16 16" aria-hidden="true"><rect x="2" y="3" width="12" height="11" rx="1.4" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2 6.5h12M5 1.8v2.6M11 1.8v2.6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>Planungsübersicht</a>
			<a href="<?php echo rkrk_pt_h($listHref); ?>"><svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="6" cy="5" r="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.4 13.2c.5-2.2 1.9-3.4 3.6-3.4s3.1 1.2 3.6 3.4" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="11.2" cy="5.6" r="1.5" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M10.2 9.9c1.2.3 2.1 1.2 2.6 3.3" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>Teilnehmerliste</a>
			<?php endif; ?>
			<?php if ($pdfHref !== ""): ?>
			<a href="<?php echo rkrk_pt_h($pdfHref); ?>"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 1.6h6L13 5.1v9.3H3.5z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9.2 1.8v3.4H12.6M5.2 8.2h5.6M5.2 10.6h5.6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>PDF</a>
			<?php if ($revLabel !== ""): ?><span class="pt-jsonrev"><?php echo rkrk_pt_h($revLabel); ?></span><?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php if (isset($_GET["ok"])): ?><div class="pt-msg pt-msg-ok">Gespeichert.</div><?php endif; ?>
	<?php if (isset($_GET["err"])): ?><div class="pt-msg pt-msg-err">Speichern fehlgeschlagen.</div><?php endif; ?>

	<?php if ($canEdit && is_array($day)):
		$share = (string) ($day["share"] ?? "");
		$shareUrl = $share !== "" ? rkrk_pt_share_url($share) : "";
		$instrShare = (string) ($day["share_instr"] ?? "");
		$instrUrl = $instrShare !== "" ? rkrk_pt_instr_url($instrShare) : "";
	?>
	<div class="pt-card pt-share">
		<h2>Freigabe</h2>
		<p class="pt-note">Nur Einsicht, ohne Bearbeitung. Instruktoren sehen zusätzlich die Kapitel mit Anzeige Instruktoren.</p>
		<div class="pt-share-line">
			<div class="pt-share-label">Teilnehmer</div>
			<?php if ($shareUrl === ""): ?>
			<form method="post">
				<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
				<input type="hidden" name="pt_action" value="share_on">
				<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
				<button class="pt-btn" type="submit">Link freigeben</button>
			</form>
			<?php else: ?>
			<div class="pt-share-main">
				<input id="pt-share-url" type="text" readonly value="<?php echo rkrk_pt_h($shareUrl); ?>" onclick="this.select();">
				<div class="pt-actions">
					<button class="pt-btn" type="button" onclick="var e=document.getElementById('pt-share-url'); e.select(); if (navigator.clipboard) { navigator.clipboard.writeText(e.value); }">Kopieren</button>
					<form method="post" onsubmit="return confirm('Der bisherige Teilnehmerlink wird ungültig.');">
						<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
						<input type="hidden" name="pt_action" value="share_off">
						<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
						<button class="pt-btn" type="submit">Link zurückziehen</button>
					</form>
				</div>
			</div>
			<div class="pt-qrs"><?php echo rkrk_pt_qr_link_html($shareUrl, "Link"); ?></div>
			<?php endif; ?>
		</div>
		<div class="pt-share-line">
			<div class="pt-share-label">Instruktoren</div>
			<?php if ($instrUrl === ""): ?>
			<form method="post">
				<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
				<input type="hidden" name="pt_action" value="share_instr_on">
				<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
				<button class="pt-btn" type="submit">Link freigeben</button>
			</form>
			<?php else: ?>
			<div class="pt-share-main">
				<input id="pt-instr-url" type="text" readonly value="<?php echo rkrk_pt_h($instrUrl); ?>" onclick="this.select();">
				<div class="pt-actions">
					<button class="pt-btn" type="button" onclick="var e=document.getElementById('pt-instr-url'); e.select(); if (navigator.clipboard) { navigator.clipboard.writeText(e.value); }">Kopieren</button>
					<form method="post" onsubmit="return confirm('Der bisherige Instruktorenlink wird ungültig.');">
						<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
						<input type="hidden" name="pt_action" value="share_instr_off">
						<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
						<button class="pt-btn" type="submit">Link zurückziehen</button>
					</form>
				</div>
			</div>
			<div class="pt-qrs"><?php echo rkrk_pt_qr_link_html($instrUrl, "Instruktion"); ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if (!$isShareView && $days !== array()): ?>
	<div class="pt-days">
		<?php foreach ($days as $d):
			if (!is_array($d)) { continue; }
			$did = (string) ($d["id"] ?? "");
			$on = ($did !== "" && $did === $dayId);
		?>
			<a class="<?php echo $on ? "on" : ""; ?>" href="<?php echo rkrk_pt_h(rkrk_pt_page_href(array("day" => $did))); ?>"><?php echo rkrk_pt_h((string) ($d["name"] ?? $did)); ?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ($canEdit): ?>
	<details class="pt-card pt-fold"<?php echo is_array($day) ? "" : " open"; ?>>
		<summary><?php echo is_array($day) ? "Einstellungen" : "Prüfungstag anlegen"; ?><?php if (is_array($day) && trim((string) ($day["name"] ?? "")) !== ""): ?> · <?php echo rkrk_pt_h((string) $day["name"]); ?><?php endif; ?></summary>
		<div class="pt-fold-body">
		<form method="post">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="<?php echo is_array($day) ? "settings" : "create"; ?>">
			<?php if (is_array($day)): ?><input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>"><?php endif; ?>
			<div class="pt-grid">
				<div>
					<label for="pt-name">Bezeichnung</label>
					<input id="pt-name" name="name" type="text" maxlength="80" value="<?php echo rkrk_pt_h(is_array($day) ? (string) ($day["name"] ?? "") : ""); ?>" required>
					<p class="pt-note">Die Bezeichnung bestimmt die Adresse, den Ordner und den Bildnamen. Die Teilnehmerliste bleibt die gewählte Liste.</p>
				</div>
				<div>
					<label for="pt-list">Teilnehmerliste</label>
					<select id="pt-list" name="list_id">
						<option value="">— wählen —</option>
						<?php foreach ($lists as $lst):
							if (!is_array($lst)) { continue; }
							$lid = (string) ($lst["id"] ?? "");
							$sel = is_array($day) && (string) ($day["list_id"] ?? "") === $lid;
						?>
						<option value="<?php echo rkrk_pt_h($lid); ?>"<?php echo $sel ? " selected" : ""; ?>><?php echo rkrk_pt_h((string) ($lst["name"] ?? $lid)); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="pt-note">Eine andere Liste löscht die Historie und setzt die Plätze in der Reihenfolge der Anmeldung.</p>
				</div>
				<?php foreach (rkrk_pt_halfday_defs() as $hd):
					$hid = $hd["id"];
					$onHalf = !is_array($day) ? ($hid !== "abend") : !empty($day["halfdays"][$hid]);
					$startVal = is_array($day) ? (string) ($day["starts"][$hid] ?? $hd["start"]) : $hd["start"];
					$groupVal = is_array($day) ? rkrk_pt_groups_of($day, $hid) : 1;
					$placeVal = is_array($day) ? rkrk_pt_places_of($day, $hid) : 3;
				?>
				<div class="pt-halfday">
					<label><?php echo rkrk_pt_h($hd["label"]); ?></label>
					<div class="pt-half">
						<input type="checkbox" name="half_<?php echo rkrk_pt_h($hid); ?>" value="1"<?php echo $onHalf ? " checked" : ""; ?> aria-label="<?php echo rkrk_pt_h($hd["label"]); ?> aktiv">
						<input name="start_<?php echo rkrk_pt_h($hid); ?>" type="text" inputmode="numeric" pattern="\d{1,2}:\d{2}" value="<?php echo rkrk_pt_h($startVal); ?>" aria-label="Start <?php echo rkrk_pt_h($hd["label"]); ?>">
						<span>Gruppen</span>
						<input class="pt-num" name="groups_<?php echo rkrk_pt_h($hid); ?>" type="number" min="1" max="6" value="<?php echo (int) $groupVal; ?>" aria-label="Gruppen <?php echo rkrk_pt_h($hd["label"]); ?>">
						<span>Arbeitsplätze</span>
						<input class="pt-num" name="places_<?php echo rkrk_pt_h($hid); ?>" type="number" min="1" max="3" value="<?php echo (int) $placeVal; ?>" aria-label="Arbeitsplätze <?php echo rkrk_pt_h($hd["label"]); ?>">
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<div class="pt-actions">
				<button class="pt-btn" type="submit"><?php echo is_array($day) ? "Speichern" : "Anlegen"; ?></button>
			</div>
			<p class="pt-note">Gruppen und Arbeitsplätze gelten je Halbtag. Jede Gruppe hat dreimal so viele Plätze wie Arbeitsplätze: Material, Flug und Theorie. Wer keinen Platz hat, steht auf der Warteliste.</p>
		</form>
		<?php if ($canEdit && is_array($day)): ?>
		<form method="post" style="margin-top:8px;" onsubmit="return confirm('Diesen Prüfungstag löschen?');">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="delete">
			<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
			<button class="pt-btn" type="submit">Prüfungstag löschen</button>
		</form>
		<?php endif; ?>
		</div>
	</details>
	<?php if (is_array($day)): ?>
	<details class="pt-card pt-fold">
		<summary>Weiterer Prüfungstag</summary>
		<div class="pt-fold-body">
		<form method="post" class="pt-grid">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="create">
			<div>
				<label for="pt-new-name">Bezeichnung</label>
				<input id="pt-new-name" name="name" type="text" maxlength="80" placeholder="z. B. EP 12.09.2026">
			</div>
			<div class="pt-actions" style="margin-top:0;">
				<button class="pt-btn" type="submit">Anlegen</button>
			</div>
		</form>
		<form method="post" class="pt-grid" style="margin-top:12px;">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="copy">
			<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
			<div>
				<label for="pt-copy-name">Kopie von <?php echo rkrk_pt_h((string) ($day["name"] ?? "dieser EP")); ?></label>
				<input id="pt-copy-name" name="name" type="text" maxlength="80" value="<?php echo rkrk_pt_h("Kopie von " . trim((string) ($day["name"] ?? "Prüfungstag"))); ?>">
			</div>
			<div class="pt-actions" style="margin-top:0;">
				<button class="pt-btn" type="submit">Daten übernehmen</button>
			</div>
		</form>
		<p class="pt-note">Die Kopie übernimmt Liste, Halbtage, Rollen, Sitzordnung, Lage und Bild. Der Teilnehmerlink bleibt leer.</p>
		</div>
	</details>
	<?php endif; ?>
	<?php endif; ?>

	<?php if (is_array($day)): ?>
	<p class="pt-cap">
		<?php
		$capBits = array();
		if (is_array($day)) {
			foreach (rkrk_pt_halfday_defs() as $hdCap) {
				if (empty($day["halfdays"][$hdCap["id"]])) {
					continue;
				}
				$capBits[] = $hdCap["label"] . ": " . rkrk_pt_groups_of($day, $hdCap["id"]) . " Gruppen × " . rkrk_pt_places_of($day, $hdCap["id"]) . " Arbeitsplätze";
			}
		}
		echo rkrk_pt_h(implode(" · ", $capBits));
		?>
		· <b><?php echo (int) $plan["capacity"]; ?></b> Plätze.
		Auf der Liste <b><?php echo (int) $seatCount; ?></b>,
		Warteliste <b><?php echo (int) $waitCount; ?></b><?php if ($skipped > 0): ?>,
		inaktiv übersprungen <b><?php echo (int) $skipped; ?></b><?php endif; ?>.
	</p>
	<?php if ($list === null): ?>
		<p class="pt-note">Noch keine Teilnehmerliste gewählt. Die Plätze bleiben leer, bis oben eine Liste gespeichert ist.</p>
	<?php elseif ($people === array()): ?>
		<p class="pt-note">Diese Teilnehmerliste enthält keine aktiven Personen.</p>
	<?php endif; ?>

	<?php
		$place = isset($day["place"]) && is_array($day["place"]) ? $day["place"] : rkrk_pt_place_defaults();
		$placeImg = rkrk_pt_place_image_fs($day);
		$placeHref = "";
		if ($placeImg !== "") {
			if ($isInstrShare && (string) ($day["share_instr"] ?? "") !== "") {
				$placeHref = rkrk_pt_instr_url((string) $day["share_instr"]) . "&bild=1";
			} elseif ($isShareView && (string) ($day["share"] ?? "") !== "") {
				$placeHref = rkrk_pt_share_url((string) $day["share"]) . "&bild=1";
			} else {
				$placeHref = rkrk_pt_page_href(array("day" => $dayId, "bild" => "1"));
			}
		}
		$placeMaps = (string) ($place["maps"] ?? "");
		if ($placeMaps === "" && (string) ($place["lat"] ?? "") !== "" && (string) ($place["lng"] ?? "") !== "") {
			$placeMaps = "https://www.google.com/maps?q=" . rawurlencode((string) $place["lat"] . "," . (string) $place["lng"]);
		}
		$placeOverlay = trim((string) ($place["overlay"] ?? ""));
		$placeAudience = $isInstrShare ? "instr" : ($isShareView ? "teil" : "admin");
		$showMaps = $placeMaps !== "" && rkrk_pt_place_link_visible($place, "maps", $placeAudience);
		$showSwiss = trim((string) ($place["swisstopo"] ?? "")) !== "" && rkrk_pt_place_link_visible($place, "swisstopo", $placeAudience);
		$showOverlay = $placeOverlay !== "" && rkrk_pt_place_link_visible($place, "overlay", $placeAudience);
		$placeShow = isset($place["show"]) && is_array($place["show"]) ? $place["show"] : rkrk_pt_place_show_defaults();
		$placeOn = static function (string $key, string $who) use ($placeShow): string {
			$row = isset($placeShow[$key]) && is_array($placeShow[$key]) ? $placeShow[$key] : array();
			return !empty($row[$who]) ? " checked" : "";
		};
		$placeHas = $placeHref !== ""
			|| trim((string) ($place["ort"] ?? "")) !== ""
			|| trim((string) ($place["note"] ?? "")) !== ""
			|| trim((string) ($place["swisstopo"] ?? "")) !== ""
			|| trim((string) ($place["maps"] ?? "")) !== ""
			|| $placeOverlay !== "";
	?>
	<?php if ($placeHas || $canEdit): ?>
	<div class="pt-card pt-place">
		<?php if ($placeHas): ?>
			<?php if (trim((string) ($place["ort"] ?? "")) !== "" || ((string) ($place["lat"] ?? "") !== "" && (string) ($place["lng"] ?? "") !== "") || $showMaps): ?>
			<p class="pt-place-ort"><b>Ort:</b>
				<?php echo rkrk_pt_h((string) ($place["ort"] ?? "")); ?>
				<?php if ((string) ($place["lat"] ?? "") !== "" && (string) ($place["lng"] ?? "") !== ""): ?>
					(<?php echo rkrk_pt_h((string) $place["lat"]); ?>, <?php echo rkrk_pt_h((string) $place["lng"]); ?>)
				<?php endif; ?>
				<?php if ($showMaps): ?><a href="<?php echo rkrk_pt_h($placeMaps); ?>" target="_blank" rel="noopener">Standort Google Maps</a><?php endif; ?>
			</p>
			<?php endif; ?>
			<?php if (trim((string) ($place["note"] ?? "")) !== ""): ?>
			<p><b>Parkplatz / Treffpunkt:</b><br><?php echo nl2br(rkrk_pt_h((string) $place["note"])); ?></p>
			<?php endif; ?>
			<?php
				$qrMaps = $showMaps ? rkrk_pt_qr_link_html($placeMaps, "Standort Google Maps") : "";
				$qrSwiss = $showSwiss ? rkrk_pt_qr_link_html((string) $place["swisstopo"], "Swisstopo") : "";
				$qrOverlay = $showOverlay ? rkrk_pt_qr_link_html($placeOverlay, "Overlay-Editor") : "";
			?>
			<?php if ($placeHref !== "" || $qrMaps !== "" || $qrSwiss !== "" || $qrOverlay !== ""): ?>
			<div class="pt-place-row">
				<?php if ($placeHref !== ""): ?>
				<img src="<?php echo rkrk_pt_h($placeHref); ?>" alt="Arbeitsplätze <?php echo rkrk_pt_h((string) ($place["ort"] ?? $day["name"] ?? "")); ?>">
				<?php endif; ?>
				<?php if ($qrMaps !== "" || $qrSwiss !== "" || $qrOverlay !== ""): ?>
				<div class="pt-qrs"><?php echo $qrMaps . $qrSwiss . $qrOverlay; ?></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			<?php if ($showSwiss || $showOverlay || ($canEdit && (string) ($place["edit"] ?? "") !== "")): ?>
			<p class="pt-place-links">
				<?php if ($showSwiss): ?><a href="<?php echo rkrk_pt_h((string) $place["swisstopo"]); ?>" target="_blank" rel="noopener">Swisstopo</a><?php endif; ?>
				<?php if ($showOverlay): ?><a href="<?php echo rkrk_pt_h($placeOverlay); ?>" target="_blank" rel="noopener">Overlay-Editor</a><?php endif; ?>
				<?php if ($canEdit && (string) ($place["edit"] ?? "") !== ""): ?><a href="<?php echo rkrk_pt_h((string) $place["edit"]); ?>" target="_blank" rel="noopener">Bearbeiten</a><?php endif; ?>
			</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ($canEdit): ?>
		<details class="pt-fold"<?php echo $placeHas ? "" : " open"; ?>>
			<summary>Lage und Bild der Arbeitsplätze</summary>
			<div class="pt-fold-body">
			<form method="post" enctype="multipart/form-data" class="pt-grid">
				<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
				<input type="hidden" name="pt_action" value="place">
				<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
				<div>
					<label for="pt-ort">Ort</label>
					<input id="pt-ort" name="place_ort" type="text" maxlength="80" value="<?php echo rkrk_pt_h((string) ($place["ort"] ?? "")); ?>">
				</div>
				<div>
					<label for="pt-lat">Breite</label>
					<input id="pt-lat" name="place_lat" type="text" inputmode="decimal" value="<?php echo rkrk_pt_h((string) ($place["lat"] ?? "")); ?>" placeholder="46.25">
				</div>
				<div>
					<label for="pt-lng">Länge</label>
					<input id="pt-lng" name="place_lng" type="text" inputmode="decimal" value="<?php echo rkrk_pt_h((string) ($place["lng"] ?? "")); ?>" placeholder="8.77">
				</div>
				<div>
					<label for="pt-maps">Standort Google Maps</label>
					<input id="pt-maps" name="place_maps" type="url" maxlength="400" value="<?php echo rkrk_pt_h((string) ($place["maps"] ?? "")); ?>" placeholder="https://maps.app.goo.gl/…">
					<div class="pt-link-flags">
						<label><input type="checkbox" name="place_show_maps_teil" value="1"<?php echo $placeOn("maps", "teil"); ?>> Teilnehmer</label>
						<label><input type="checkbox" name="place_show_maps_instr" value="1"<?php echo $placeOn("maps", "instr"); ?>> Instruktoren</label>
					</div>
				</div>
				<div class="pt-halfday">
					<label for="pt-meet">Parkplatz / Treffpunkt</label>
					<textarea id="pt-meet" name="place_note" maxlength="600"><?php echo rkrk_pt_h((string) ($place["note"] ?? "")); ?></textarea>
				</div>
				<div>
					<label for="pt-bild">Bild der Arbeitsplätze (PNG oder JPG)</label>
					<input id="pt-bild" name="place_image" type="file" accept="image/png,image/jpeg,.png,.jpg,.jpeg">
				</div>
				<div>
					<label for="pt-swiss">Swisstopo</label>
					<input id="pt-swiss" name="place_swisstopo" type="url" maxlength="400" value="<?php echo rkrk_pt_h((string) ($place["swisstopo"] ?? "")); ?>" placeholder="https://s.geo.admin.ch/…">
					<div class="pt-link-flags">
						<label><input type="checkbox" name="place_show_swisstopo_teil" value="1"<?php echo $placeOn("swisstopo", "teil"); ?>> Teilnehmer</label>
						<label><input type="checkbox" name="place_show_swisstopo_instr" value="1"<?php echo $placeOn("swisstopo", "instr"); ?>> Instruktoren</label>
					</div>
				</div>
				<div>
					<label for="pt-overlay">Overlay-Editor</label>
					<input id="pt-overlay" name="place_overlay" type="url" maxlength="400" value="<?php echo rkrk_pt_h($placeOverlay); ?>" placeholder="https://…">
					<div class="pt-link-flags">
						<label><input type="checkbox" name="place_show_overlay_teil" value="1"<?php echo $placeOn("overlay", "teil"); ?>> Teilnehmer</label>
						<label><input type="checkbox" name="place_show_overlay_instr" value="1"<?php echo $placeOn("overlay", "instr"); ?>> Instruktoren</label>
					</div>
				</div>
				<div>
					<label for="pt-swiss-edit">Swisstopo bearbeiten</label>
					<input id="pt-swiss-edit" name="place_edit" type="url" maxlength="400" value="<?php echo rkrk_pt_h((string) ($place["edit"] ?? "")); ?>" placeholder="https://s.geo.admin.ch/…">
				</div>
				<?php if ($placeHref !== ""): ?>
				<div class="pt-halfday pt-clear">
					<label><input type="checkbox" name="place_clear" value="1"> Bild der Arbeitsplätze entfernen</label>
				</div>
				<?php endif; ?>
				<div class="pt-actions">
					<button class="pt-btn" type="submit">Lage speichern</button>
				</div>
			</form>
			</div>
		</details>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php
		$chapterHtml = rkrk_pt_chapters_html($day, $showStaffChapters, false);
		if ($chapterHtml !== "" || $canEdit):
	?>
	<div class="pt-card pt-chapters">
		<?php echo $chapterHtml; ?>
		<?php if ($canEdit) { echo rkrk_pt_chapter_editor_html($day); } ?>
	</div>
	<?php endif; ?>

	<?php if ($canEdit && is_array($day)): ?>
	<form method="post" class="pt-card pt-pub">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<input type="hidden" name="pt_action" value="roster_pub">
		<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
		<span>Kontaktlisten, ohne Test und Bezahlt</span>
		<span class="pt-pub-set"><b>Instruktoren</b>
			<label><input type="checkbox" name="roster_instr_teil_link" value="1"<?php echo !empty($day["roster_instr_teil_link"]) ? " checked" : ""; ?>> Teilnehmerlink</label>
			<label><input type="checkbox" name="roster_instr_instr_link" value="1"<?php echo !empty($day["roster_instr_instr_link"]) ? " checked" : ""; ?>> Instruktorenlink</label>
			<label><input type="checkbox" name="roster_instr_pdf" value="1"<?php echo !empty($day["roster_instr_pdf"]) ? " checked" : ""; ?>> PDF</label>
		</span>
		<span class="pt-pub-set"><b>Teilnehmer</b>
			<label><input type="checkbox" name="roster_teil_teil_link" value="1"<?php echo !empty($day["roster_teil_teil_link"]) ? " checked" : ""; ?>> Teilnehmerlink</label>
			<label><input type="checkbox" name="roster_teil_instr_link" value="1"<?php echo !empty($day["roster_teil_instr_link"]) ? " checked" : ""; ?>> Instruktorenlink</label>
			<label><input type="checkbox" name="roster_teil_pdf" value="1"<?php echo !empty($day["roster_teil_pdf"]) ? " checked" : ""; ?>> PDF</label>
		</span>
		<button class="pt-btn" type="submit">Speichern</button>
	</form>
	<?php endif; ?>

	<?php
	$rosterWithTest = !$isShareView;
	$ptFeeDue = false;
	if (is_array($day) && function_exists("rkrk_tr_list_by_id")) {
		$ptListFee = rkrk_tr_list_by_id((string) ($day["list_id"] ?? ""), isset($trCfg) ? $trCfg : null);
		$ptFeeDue = is_array($ptListFee) && !empty($ptListFee["fee_due"]);
	}
	$pilotContacts = false;
	$showInstrRoster = false;
	if (is_array($day)) {
		if ($isInstrShare) {
			$showInstrRoster = !empty($day["roster_instr_instr_link"]);
			$pilotContacts = !empty($day["roster_teil_instr_link"]);
		} elseif ($isShareView) {
			$showInstrRoster = !empty($day["roster_instr_teil_link"]);
			$pilotContacts = !empty($day["roster_teil_teil_link"]);
		} else {
			$showInstrRoster = true;
			$pilotContacts = !empty($day["roster_teil_teil_link"]) || !empty($day["roster_teil_instr_link"]) || !empty($day["roster_teil_pdf"]);
		}
	}
	$pilotIds = $pilotContacts && ($isInstrShare || !$isShareView);
	$instrRosterIds = $isInstrShare || !$isShareView;
	$ptPersonIndex = $pilotContacts ? rkrk_pt_person_index() : array();
	$echoHalfLists = static function (string $halfId) use ($day, $plan, $rosterWithTest, $canEdit, $ptFeeDue, $isShareView, $showInstrRoster, $instrRosterIds): void {
		if (!is_array($day)) {
			return;
		}
		$showInstr = $showInstrRoster;
		$showTeil = !$isShareView;
		if (!$showInstr && !$showTeil) {
			return;
		}
		$roster = rkrk_pt_half_roster($day, $plan, $halfId);
		if ($showInstr) {
			$instrBtn = $canEdit ? rkrk_pt_mail_copy_button("Mails kopieren", rkrk_pt_mail_join($roster["instr"])) : "";
			echo "<div class=\"pt-card\"><div class=\"pt-mailhead\"><h3>Instruktoren</h3>" . $instrBtn . "</div><div class=\"pt-roster-wrap\">"
				. rkrk_pt_roster_table_html($roster["instr"], false, false, false, $instrRosterIds) . "</div></div>";
		}
		if ($showTeil) {
			$teilBtn = $canEdit ? rkrk_pt_mail_copy_button("Mails kopieren", rkrk_pt_mail_join($roster["teilnehmer"])) : "";
			echo "<div class=\"pt-card\"><div class=\"pt-mailhead\"><h3>Teilnehmer</h3>" . $teilBtn . "</div><div class=\"pt-roster-wrap\">"
				. rkrk_pt_roster_table_html($roster["teilnehmer"], false, $rosterWithTest, $ptFeeDue && $rosterWithTest, false) . "</div></div>";
		}
	};
	$allInstrRows = array();
	$allTeilRows = array();
	if (is_array($day) && $canEdit) {
		$seenHalf = array();
		foreach ($plan["blocks"] as $mailBlock) {
			if (!is_array($mailBlock)) {
				continue;
			}
			$mailHalf = (string) ($mailBlock["half_id"] ?? "");
			if ($mailHalf === "" || isset($seenHalf[$mailHalf])) {
				continue;
			}
			$seenHalf[$mailHalf] = true;
			$mailRoster = rkrk_pt_half_roster($day, $plan, $mailHalf);
			foreach ($mailRoster["instr"] as $mailRow) {
				$allInstrRows[] = $mailRow;
			}
			foreach ($mailRoster["teilnehmer"] as $mailRow) {
				$allTeilRows[] = $mailRow;
			}
		}
		$mailIndex = rkrk_pt_person_index();
		$mailWait = isset($plan["wait"]) && is_array($plan["wait"]) ? $plan["wait"] : array();
		foreach ($mailWait as $mailPerson) {
			if (!is_array($mailPerson)) {
				continue;
			}
			$mailPid = trim((string) ($mailPerson["id"] ?? ""));
			$mailRow = ($mailPid !== "" && isset($mailIndex[$mailPid])) ? $mailIndex[$mailPid] : null;
			$allTeilRows[] = rkrk_pt_contact_fields(is_array($mailRow) ? $mailRow : null, $mailPid);
		}
	}
	$lastHalf = "";
	$lastHalfId = "";
	$planTitleShown = false;
	$ptMarkCount = is_array($day) ? rkrk_pt_mark_count($day) : 0;
	if ($ptMarkCount > 0): ?>
	<div class="pt-chg-bar">
		<span>Geänderte Einteilungen sind 7 Tage hellgelb markiert.</span>
		<?php if ($canEdit): ?>
		<form method="post">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="clear_marks">
			<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
			<button class="pt-btn" type="submit">Markierungen zurücksetzen</button>
		</form>
		<?php endif; ?>
	</div>
	<?php endif;
	if ($allInstrRows !== array() || $allTeilRows !== array()): ?>
	<div class="pt-card pt-mailbar">
		<span>Mails kopieren</span>
		<?php echo rkrk_pt_mail_copy_button("Alle Instruktoren", rkrk_pt_mail_join($allInstrRows)); ?>
		<?php echo rkrk_pt_mail_copy_button("Alle Teilnehmer", rkrk_pt_mail_join($allTeilRows)); ?>
	</div>
	<?php endif;
	foreach ($plan["blocks"] as $block):
		if (!is_array($block)) { continue; }
		if ((string) $block["half_label"] !== $lastHalf):
			if ($lastHalf !== "") {
				echo "</div>";
				$echoHalfLists($lastHalfId);
			}
			if (!$planTitleShown) {
				echo "<h2 class=\"pt-plan\">Zeitplan und Einteilung</h2>";
				$planTitleShown = true;
			}
			$lastHalf = (string) $block["half_label"];
			$lastHalfId = (string) $block["half_id"];
	?>
		<h2 class="pt-sec"><?php echo rkrk_pt_h($lastHalf); ?> ab <?php echo rkrk_pt_h((string) $block["start"]); ?> · <?php echo (int) ($block["groups"] ?? 1); ?> <?php echo (int) ($block["groups"] ?? 1) === 1 ? "Gruppe" : "Gruppen"; ?> · <?php echo (int) ($block["places"] ?? 3); ?> Arbeitsplätze</h2>
		<div class="pt-lanes">
	<?php endif; ?>
		<div class="pt-card">
			<?php $seatLast = $block["seats"][count($block["seats"]) - 1]; $blockPlaces = max(1, (int) ($block["places"] ?? 3)); ?>
			<h2><?php echo rkrk_pt_h((string) $block["label"]); ?> · P<?php echo (int) $block["seats"][0]["nr"]; ?>–P<?php echo (int) ($seatLast["nr"] ?? 0); ?></h2>
			<?php
				$staffBlock = rkrk_pt_staff_block($day, (string) $block["half_id"], (int) $block["lane"]);
				$staffNames = isset($staffCatalog["names"]) && is_array($staffCatalog["names"]) ? $staffCatalog["names"] : array();
			?>
			<?php
				$leadNow = rkrk_pt_staff_lead($day, (string) $block["half_id"]);
				$leadRows = array(
					array("id" => "tageschef", "label" => "Tageschef"),
					array("id" => "techn", "label" => "Technischer Experte"),
				);
				$showLead = (int) $block["lane"] === 1;
			?>
			<?php if ($canEdit): ?>
			<form method="post" class="pt-staff">
				<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
				<input type="hidden" name="pt_action" value="staff">
				<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
				<input type="hidden" name="half_id" value="<?php echo rkrk_pt_h((string) $block["half_id"]); ?>">
				<input type="hidden" name="lane" value="<?php echo (int) $block["lane"]; ?>">
			<?php endif; ?>
			<?php if ($showStaffPlan): ?>
			<table class="pt-matrix">
				<thead><tr><th>Rolle</th><?php for ($pi = 1; $pi <= $blockPlaces; $pi++): ?><th>Platz <?php echo $pi; ?></th><?php endfor; ?></tr></thead>
				<tbody>
				<?php if ($showLead): foreach ($leadRows as $role):
					$leadId = (string) ($leadNow[$role["id"]] ?? "");
					$leadName = $leadId !== "" ? (string) ($staffNames[$leadId] ?? $leadId) : "";
					$leadKey = "l:" . (string) $block["half_id"] . ":" . $role["id"];
					$leadChg = rkrk_pt_mark_is($day, $leadKey);
				?>
					<tr>
						<td><?php echo rkrk_pt_h($role["label"]); ?></td>
						<td colspan="<?php echo (int) $blockPlaces; ?>"<?php echo $leadChg ? " class=\"pt-chg\" title=\"" . rkrk_pt_h(rkrk_pt_mark_title($day, $leadKey)) . "\"" : ""; ?>>
							<?php if ($canEdit): ?>
							<select name="lead[<?php echo rkrk_pt_h($role["id"]); ?>]">
								<option value="">—</option>
								<?php echo rkrk_pt_staff_options_html($staffCatalog, $leadId); ?>
							</select>
							<?php else: ?>
							<?php echo rkrk_pt_h($leadName !== "" ? $leadName : "—"); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				<?php foreach (rkrk_pt_station_roles() as $role):
					$slots = $staffBlock["roles"][$role["id"]] ?? array("", "", "");
				?>
					<tr>
						<td><?php echo rkrk_pt_h($role["label"]); ?></td>
						<?php for ($si = 0; $si < $blockPlaces; $si++):
							$pid = trim((string) ($slots[$si] ?? ""));
							$label = $pid !== "" ? (string) ($staffNames[$pid] ?? $pid) : "";
							$slotKey = "s:" . (string) $block["half_id"] . ":" . (int) $block["lane"] . ":" . $role["id"] . ":" . $si;
							$slotChg = rkrk_pt_mark_is($day, $slotKey);
						?>
						<td<?php echo $slotChg ? " class=\"pt-chg\" title=\"" . rkrk_pt_h(rkrk_pt_mark_title($day, $slotKey)) . "\"" : ""; ?>>
							<?php if ($canEdit): ?>
							<select name="slot[<?php echo rkrk_pt_h($role["id"]); ?>][<?php echo $si; ?>]">
								<option value="">—</option>
								<?php echo rkrk_pt_staff_options_html($staffCatalog, $pid); ?>
							</select>
							<?php else: ?>
							<?php echo rkrk_pt_h($label !== "" ? $label : "—"); ?>
							<?php endif; ?>
						</td>
						<?php endfor; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ($canEdit): ?>
				<label class="pt-note" for="pt-note-<?php echo rkrk_pt_h((string) $block["half_id"] . "-" . (string) $block["lane"]); ?>">Hinweis</label>
				<textarea id="pt-note-<?php echo rkrk_pt_h((string) $block["half_id"] . "-" . (string) $block["lane"]); ?>" name="note" maxlength="400"><?php echo rkrk_pt_h((string) ($staffBlock["note"] ?? "")); ?></textarea>
				<div class="pt-actions"><button class="pt-btn" type="submit">Instruktoren speichern</button></div>
			</form>
			<?php elseif ($showStaffPlan && trim((string) ($staffBlock["note"] ?? "")) !== ""): ?>
				<p class="pt-note"><?php echo nl2br(rkrk_pt_h((string) $staffBlock["note"])); ?></p>
			<?php endif; ?>
			<?php endif; ?>
			<div class="pt-split">
				<div>
					<h3 style="margin:0 0 6px;font-size:14px;">Teilnehmer zum Start</h3>
					<table class="pt-matrix">
						<thead><tr><th></th><?php for ($pi = 1; $pi <= $blockPlaces; $pi++): ?><th>Platz <?php echo $pi; ?></th><?php endfor; ?></tr></thead>
						<tbody>
						<?php
						$startMap = array(
							array("Start Material", range(1, $blockPlaces)),
							array("Start Flug", range($blockPlaces + 1, $blockPlaces * 2)),
							array("Start Theorie", range($blockPlaces * 2 + 1, $blockPlaces * 3)),
						);
						foreach ($startMap as $startRow):
						?>
							<tr>
								<td><?php echo rkrk_pt_h($startRow[0]); ?></td>
								<?php foreach ($startRow[1] as $local):
									$seat = $block["seats"][$local - 1] ?? null;
									$person = is_array($seat) && is_array($seat["person"] ?? null) ? $seat["person"] : null;
									$nr = is_array($seat) ? (int) ($seat["nr"] ?? $local) : $local;
									$nm = is_array($person) ? (string) ($person["name"] ?? "") : "";
									$startPid = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
									$startChg = $startPid !== "" && rkrk_pt_mark_is($day, "p:" . $startPid);
									$startCls = trim(($nm === "" ? "pt-free" : "") . ($startChg ? " pt-chg" : ""));
								?>
								<td<?php echo $startCls !== "" ? " class=\"" . rkrk_pt_h($startCls) . "\"" : ""; ?><?php echo $startChg ? " title=\"" . rkrk_pt_h(rkrk_pt_mark_title($day, "p:" . $startPid)) . "\"" : ""; ?>><?php echo rkrk_pt_h("P" . $nr . ($nm !== "" ? " " . $nm : "")); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php if ($pilotContacts): ?><div class="pt-roster-wrap"><?php endif; ?>
			<table class="pt-table pt-seatrows<?php echo $pilotContacts ? " pt-seatwide" : ""; ?>">
				<thead><tr>
					<th>Pilot</th><th>Name</th>
					<?php if ($pilotContacts): ?><th>Wohnort</th><th>Telefon</th><th>E-Mail</th><?php endif; ?>
					<th>Drohne</th>
					<?php if ($pilotIds): ?><th>Betreiber-Nr.</th><th>Piloten-ID</th><?php endif; ?>
				</tr></thead>
				<tbody class="pt-seatbody" data-pt-seats="1">
				<?php foreach ($block["seats"] as $seat):
					$person = is_array($seat["person"] ?? null) ? $seat["person"] : null;
					$pname = is_array($person) ? (string) ($person["name"] ?? "") : "";
					$seatPid = is_array($person) ? trim((string) ($person["id"] ?? "")) : "";
					$seatChg = $seatPid !== "" && rkrk_pt_mark_is($day, "p:" . $seatPid);
					$seatContact = $pilotContacts ? rkrk_pt_seat_contacts(is_array($person) ? $person : null, $ptPersonIndex) : null;
				?>
					<tr<?php echo $seatChg ? " class=\"pt-chg\"" : ""; ?><?php if ($canEdit && $seatPid !== ""): ?> draggable="true" data-pt-pid="<?php echo rkrk_pt_h($seatPid); ?>"<?php endif; ?><?php echo $seatChg ? " title=\"" . rkrk_pt_h(rkrk_pt_mark_title($day, "p:" . $seatPid)) . "\"" : ""; ?>>
						<td>P<?php echo (int) ($seat["nr"] ?? 0); ?></td>
						<td class="<?php echo $pname === "" ? "pt-free" : ""; ?>"><?php if ($canEdit && $seatPid !== ""): ?><span class="pt-grip" aria-hidden="true">⋮⋮</span> <?php endif; ?><?php echo rkrk_pt_h($pname !== "" ? $pname : "frei"); ?><?php if ($canEdit && $seatPid !== ""): ?> <?php echo rkrk_pt_withdraw_form($dayId, $seatPid); ?><?php endif; ?></td>
						<?php if (is_array($seatContact)): ?>
						<td><?php echo rkrk_pt_h($seatContact["ort"]); ?></td>
						<td><?php echo rkrk_pt_h($seatContact["telefon"]); ?></td>
						<td><?php echo rkrk_pt_h($seatContact["email"]); ?></td>
						<?php endif; ?>
						<td><?php echo rkrk_pt_h(is_array($seatContact) ? $seatContact["drohne"] : (is_array($person) ? (string) ($person["drone"] ?? "") : "")); ?></td>
						<?php if ($pilotIds && is_array($seatContact)): ?>
						<td><?php echo rkrk_pt_h($seatContact["betreiber"]); ?></td>
						<td><?php echo rkrk_pt_h($seatContact["pilotenid"]); ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ($pilotContacts): ?></div><?php endif; ?>
			<?php echo rkrk_pt_rotation_table_html($block["seats"], (string) $block["start"], null, false, rkrk_pt_changed_pid_set($day)); ?>
		</div>
	<?php endforeach; ?>
	<?php if ($lastHalf !== "") {
		echo "</div>";
		$echoHalfLists($lastHalfId);
	} ?>
	<?php if ($canEdit): ?>
	<script>
	document.querySelectorAll("[data-pt-mails]").forEach(function (btn) {
		btn.addEventListener("click", function () {
			var text = btn.getAttribute("data-pt-mails") || "";
			if (text === "") { return; }
			var old = btn.textContent;
			var done = function () {
				btn.textContent = "kopiert";
				window.setTimeout(function () { btn.textContent = old; }, 1400);
			};
			var fallback = function () {
				var ta = document.createElement("textarea");
				ta.value = text;
				ta.setAttribute("readonly", "");
				ta.style.position = "fixed";
				ta.style.left = "-9999px";
				document.body.appendChild(ta);
				ta.select();
				try { document.execCommand("copy"); done(); } catch (e) {}
				document.body.removeChild(ta);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(done, fallback);
			} else {
				fallback();
			}
		});
	});
	</script>
	<?php endif; ?>

	<div class="pt-card">
		<h2>Warteliste<?php if ($waitCount > 0): ?> (<?php echo (int) $waitCount; ?>)<?php endif; ?></h2>
		<?php if ($plan["wait"] === array()): ?>
			<p class="pt-note">Niemand wartet. Alle Personen der Liste haben einen Platz, oder die Liste ist kürzer als die Kapazität.</p>
		<?php else: ?>
		<table class="pt-table pt-seatrows">
			<thead><tr><th></th><th>Name</th><th>Drohne</th></tr></thead>
			<tbody class="pt-seatbody" data-pt-wait="1">
			<?php $w = 1; foreach ($plan["wait"] as $person):
				$waitPid = trim((string) ($person["id"] ?? ""));
				$waitChg = $waitPid !== "" && rkrk_pt_mark_is($day, "p:" . $waitPid);
				$waitCls = trim(($waitChg ? "pt-chg" : "") . ($w === 2 ? " pt-wait-ready" : ""));
			?>
				<tr<?php echo $waitCls !== "" ? " class=\"" . $waitCls . "\"" : ""; ?><?php if ($canEdit && $waitPid !== ""): ?> draggable="true" data-pt-pid="<?php echo rkrk_pt_h($waitPid); ?>"<?php endif; ?><?php echo $waitChg ? " title=\"" . rkrk_pt_h(rkrk_pt_mark_title($day, "p:" . $waitPid)) . "\"" : ""; ?>>
					<td>W<?php echo (int) $w; ?></td>
					<td><?php if ($canEdit && $waitPid !== ""): ?><span class="pt-grip" aria-hidden="true">⋮⋮</span> <?php endif; ?><?php echo rkrk_pt_h((string) ($person["name"] ?? "")); ?><?php if ($canEdit && $waitPid !== ""): ?> <?php echo rkrk_pt_withdraw_form($dayId, $waitPid); ?><?php endif; ?></td>
					<td><?php echo rkrk_pt_h((string) ($person["drone"] ?? "")); ?></td>
				</tr>
			<?php if ($w === 2): ?>
				<tr class="pt-wait-cut" data-pt-cut="1">
					<td colspan="3">Diese zwei bereiten sich vor und rücken nach, wenn jemand ausfällt.</td>
				</tr>
			<?php endif; ?>
			<?php $w++; endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
	<?php if ($canEdit): ?>
	<?php
		$naGroups = function_exists("rkrk_na_groups") ? rkrk_na_groups() : array();
		$logRows = array_reverse(rkrk_pt_normalize_log($day["log"] ?? array()));
		$offNow = rkrk_pt_withdrawn_set($day);
	?>
	<details class="pt-card pt-fold">
		<summary>Nicht zugeordnete Namen<?php if ($naGroups !== array()): ?> (<?php echo count($naGroups); ?>)<?php endif; ?></summary>
		<div class="pt-fold-body">
			<p class="pt-note">Ganze Testgruppen mit ungültigem Namen werden auf der Planungsübersicht einem Mitglied zugewiesen.<?php if ($naGroups !== array()): ?> Offen: <?php
				$naLabels = array();
				foreach ($naGroups as $naGroup) {
					$naLabels[] = (string) ($naGroup["name"] ?? "") . " (" . count($naGroup["items"] ?? array()) . ")";
				}
				echo rkrk_pt_h(implode(", ", $naLabels));
			?>.<?php endif; ?></p>
			<p class="pt-note"><a href="<?php echo rkrk_pt_h($planningHref); ?>">Zur Planungsübersicht</a></p>
		</div>
	</details>
	<details class="pt-card pt-fold">
		<summary>Historie<?php if ($logRows !== array()): ?> (<?php echo count($logRows); ?>)<?php endif; ?></summary>
		<div class="pt-fold-body">
			<p class="pt-note">Abgemeldete Personen stehen nur hier und sind aus der Teilnehmerliste entfernt. Wieder anmelden setzt sie ans Ende der Warteliste und wieder in die Liste. Die Liste selbst führt keine Historie.</p>
			<?php if ($logRows === array()): ?>
				<p class="pt-note">Noch keine Einträge. Bestehende Plätze hatten vor dieser Historie keinen Zeitpunkt.</p>
			<?php else: ?>
			<table class="pt-log">
				<thead><tr><th>Zeit</th><th>Person</th><th>Ereignis</th><th></th></tr></thead>
				<tbody>
				<?php
				$reenrollShown = array();
				foreach ($logRows as $entry):
					$epid = (string) ($entry["pid"] ?? "");
					$showBack = ($entry["action"] ?? "") === "abgemeldet" && isset($offNow[$epid]) && !isset($reenrollShown[$epid]);
					if ($showBack) {
						$reenrollShown[$epid] = true;
					}
				?>
				<tr>
					<td><?php echo rkrk_pt_h((string) ($entry["at"] ?? "")); ?></td>
					<td><?php echo rkrk_pt_h((string) ($entry["name"] ?? $epid)); ?></td>
					<td><?php echo rkrk_pt_h((string) ($entry["action"] ?? "")); ?></td>
					<td><?php if ($showBack): ?>
						<form method="post" style="display:inline;">
							<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
							<input type="hidden" name="pt_action" value="enroll">
							<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
							<input type="hidden" name="pilot_id" value="<?php echo rkrk_pt_h($epid); ?>">
							<button type="submit" class="pt-on" title="Wieder anmelden. Kommt ans Ende der Warteliste und zurück in die Teilnehmerliste." aria-label="Wieder anmelden"><svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="6" cy="5.2" r="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.2 13.2c.6-2.1 2-3.2 3.8-3.2s3.2 1.1 3.8 3.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11.2 8.2h3.6M13 6.4v3.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
						</form>
					<?php endif; ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</details>
	<?php endif; ?>
	<?php endif; ?>
	<?php if ($canEdit && is_array($day)): ?>
	<form id="pt-reorder-form" method="post" hidden>
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<input type="hidden" name="pt_action" value="reorder">
		<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
		<input type="hidden" name="order_ids" id="pt-reorder-ids" value="">
	</form>
	<script>
	(function () {
		var form = document.getElementById("pt-reorder-form");
		var field = document.getElementById("pt-reorder-ids");
		if (!form || !field) { return; }
		var drag = null;
		var start = "";
		var place = null;
		function ids() {
			var out = [];
			document.querySelectorAll("tbody[data-pt-seats]").forEach(function (body) {
				body.querySelectorAll("tr").forEach(function (row) {
					out.push(row.getAttribute("data-pt-pid") || "");
				});
			});
			document.querySelectorAll("tbody[data-pt-wait] tr[data-pt-pid]").forEach(function (row) {
				out.push(row.getAttribute("data-pt-pid") || "");
			});
			return out.join(",");
		}
		var sent = false;
		function commit() {
			if (sent) { return; }
			var next = ids();
			if (next === "" || next === start) { return; }
			sent = true;
			field.value = next;
			form.submit();
		}
		function clearDrop() {
			document.querySelectorAll(".pt-drop, .pt-drop-after").forEach(function (node) {
				node.classList.remove("pt-drop");
				node.classList.remove("pt-drop-after");
			});
		}
		document.querySelectorAll("tr[data-pt-pid]").forEach(function (row) {
			row.addEventListener("dragstart", function (event) {
				drag = row;
				place = null;
				start = ids();
				row.classList.add("pt-dragging");
				if (event.dataTransfer) {
					event.dataTransfer.effectAllowed = "move";
					event.dataTransfer.setData("text/plain", row.getAttribute("data-pt-pid") || "");
				}
			});
			row.addEventListener("dragend", function () {
				row.classList.remove("pt-dragging");
				clearDrop();
				drag = null;
				place = null;
			});
		});
		document.querySelectorAll(".pt-seatbody").forEach(function (body) {
			body.addEventListener("dragover", function (event) {
				if (!drag) { return; }
				event.preventDefault();
				if (event.dataTransfer) { event.dataTransfer.dropEffect = "move"; }
				var target = event.target && event.target.closest ? event.target.closest("tr") : null;
				if (target && target.hasAttribute("data-pt-cut")) {
					target = target.previousElementSibling;
				}
				clearDrop();
				if (!target || !body.contains(target) || target === drag) {
					var rows = body.querySelectorAll("tr[data-pt-pid]");
					var last = rows.length ? rows[rows.length - 1] : null;
					if (!last || last === drag) {
						place = null;
						return;
					}
					last.classList.add("pt-drop-after");
					place = { body: body, row: last, after: true };
					return;
				}
				var rect = target.getBoundingClientRect();
				var after = event.clientY > rect.top + rect.height / 2;
				target.classList.add(after ? "pt-drop-after" : "pt-drop");
				place = { body: body, row: target, after: after };
			});
			body.addEventListener("drop", function (event) {
				event.preventDefault();
				if (!drag || !place || !place.body) { return; }
				var row = drag;
				if (place.row && place.body.contains(place.row)) {
					place.body.insertBefore(row, place.after ? place.row.nextSibling : place.row);
				} else {
					place.body.appendChild(row);
				}
				place = null;
				commit();
			});
		});
	}());
	</script>
	<?php endif; ?>
	<dialog id="pt-img-pop" class="pt-img-pop">
		<button type="button" class="pt-qr-pop-x" aria-label="Schliessen">×</button>
		<img alt="">
		<p></p>
	</dialog>
	<dialog id="pt-qr-pop" class="pt-qr-pop">
		<button type="button" class="pt-qr-pop-x" aria-label="Schliessen">×</button>
		<div class="pt-qr-pop-body"></div>
		<p></p>
	</dialog>
	<script>
	(function () {
		var pop = document.getElementById("pt-qr-pop");
		if (!pop || !pop.showModal) { return; }
		var body = pop.querySelector(".pt-qr-pop-body");
		var caption = pop.querySelector("p");
		document.querySelectorAll(".pt-qr-open").forEach(function (btn) {
			btn.addEventListener("dblclick", function (event) {
				event.preventDefault();
				var svg = btn.querySelector("svg");
				if (!svg || !body) { return; }
				body.innerHTML = "";
				body.appendChild(svg.cloneNode(true));
				if (caption) { caption.textContent = btn.getAttribute("data-pt-qr-label") || ""; }
				if (!pop.open) { pop.showModal(); }
			});
		});
		var close = pop.querySelector(".pt-qr-pop-x");
		if (close) { close.addEventListener("click", function () { pop.close(); }); }
		pop.addEventListener("click", function (event) {
			if (event.target === pop) { pop.close(); }
		});
	}());
	</script>
	<script>
	(function () {
		var pop = document.getElementById("pt-img-pop");
		if (!pop || !pop.showModal) { return; }
		var img = pop.querySelector("img");
		var caption = pop.querySelector("p");
		document.addEventListener("dblclick", function (event) {
			var source = event.target.closest(".pt-chapter-img");
			if (!source || !img) { return; }
			event.preventDefault();
			img.src = source.currentSrc || source.src;
			img.alt = source.alt || "";
			if (caption) { caption.textContent = source.alt || ""; }
			if (!pop.open) { pop.showModal(); }
		});
		var close = pop.querySelector(".pt-qr-pop-x");
		if (close) { close.addEventListener("click", function () { pop.close(); }); }
		pop.addEventListener("click", function (event) {
			if (event.target === pop) { pop.close(); }
		});
		document.addEventListener("keydown", function (event) {
			if (event.key === "Escape" && pop.open) { pop.close(); }
		});
	}());
	</script>
</div>
<?php
if (function_exists("rkrk_assess_footer_echo")) {
	rkrk_assess_footer_echo();
}

/**
 * @param array{instr?:list<array<string,mixed>>,other?:list<array<string,mixed>>,names?:array<string,string>} $catalog
 */
function rkrk_pt_staff_options_html(array $catalog, string $selected): string {
	$html = "";
	$seen = array();
	$groups = array(
		"Instruktoren" => isset($catalog["instr"]) && is_array($catalog["instr"]) ? $catalog["instr"] : array(),
		"Personal" => isset($catalog["other"]) && is_array($catalog["other"]) ? $catalog["other"] : array(),
	);
	foreach ($groups as $label => $rows) {
		if ($rows === array()) {
			continue;
		}
		$html .= "<optgroup label=\"" . rkrk_pt_h($label) . "\">";
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$key = (string) ($row["key"] ?? "");
			if ($key === "") {
				continue;
			}
			$seen[$key] = true;
			$sel = ($key === $selected) ? " selected" : "";
			$html .= "<option value=\"" . rkrk_pt_h($key) . "\"" . $sel . ">" . rkrk_pt_h((string) ($row["name"] ?? $key)) . "</option>";
		}
		$html .= "</optgroup>";
	}
	if ($selected !== "" && !isset($seen[$selected])) {
		$name = (string) ($catalog["names"][$selected] ?? $selected);
		$html .= "<option value=\"" . rkrk_pt_h($selected) . "\" selected>" . rkrk_pt_h($name) . "</option>";
	}
	return $html;
}

function rkrk_pt_withdraw_form(string $dayId, string $pilotId): string {
	if ($pilotId === "") {
		return "";
	}
	ob_start();
	?>
	<form method="post" style="display:inline;" onsubmit="return confirm('Teilnehmer abmelden? Die erste Person der Warteliste rückt auf diesen Platz.');">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<input type="hidden" name="pt_action" value="withdraw">
		<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
		<input type="hidden" name="pilot_id" value="<?php echo rkrk_pt_h($pilotId); ?>">
		<button type="submit" class="pt-off" title="Abmelden" aria-label="Abmelden"><svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="6" cy="5.2" r="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.2 13.2c.6-2.1 2-3.2 3.8-3.2s3.2 1.1 3.8 3.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11.2 8.2h3.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
	</form>
	<?php
	return (string) ob_get_clean();
}

function rkrk_pt_move_html(string $dayId, string $pilotId): string {
	if ($pilotId === "") {
		return "";
	}
	$hidden = function_exists("rkrk_assess_echo_setting_hidden_input") ? "" : "";
	ob_start();
	?>
	<span class="pt-move">
		<form method="post" style="display:inline;">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="move">
			<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
			<input type="hidden" name="pilot_id" value="<?php echo rkrk_pt_h($pilotId); ?>">
			<input type="hidden" name="dir" value="up">
			<button type="submit" title="Nach oben" aria-label="Nach oben">hoch</button>
		</form>
		<form method="post" style="display:inline;">
			<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
			<input type="hidden" name="pt_action" value="move">
			<input type="hidden" name="day" value="<?php echo rkrk_pt_h($dayId); ?>">
			<input type="hidden" name="pilot_id" value="<?php echo rkrk_pt_h($pilotId); ?>">
			<input type="hidden" name="dir" value="down">
			<button type="submit" title="Nach unten" aria-label="Nach unten">runter</button>
		</form>
	</span>
	<?php
	return (string) ob_get_clean();
}
