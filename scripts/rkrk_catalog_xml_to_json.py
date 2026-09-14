# Convert RKRK_ALLKMLStyles.xml and RKRK_SummaryFluege.xml to rkrk/webdaten/
import json
import re
import xml.etree.ElementTree as ET
from pathlib import Path

MOJI = {
    "Ã¤": "ä", "Ã„": "Ä", "Ã¶": "ö", "Ã–": "Ö",
    "Ã¼": "ü", "Ãœ": "Ü", "Ã": "ß",
    "Ã©": "é", "Ã¨": "è", "Ãª": "ê", "Ã«": "ë",
    "Ã¡": "á", "Ã ": "à", "Ã¢": "â",
    "Ã´": "ô", "Ã¹": "ù", "Ã»": "û", "Ã§": "ç",
    "Ã±": "ñ",
}

def utf8_text(s):
    if s is None:
        return ""
    t = str(s)
    if t.startswith("\ufeff"):
        t = t[1:]
    for _ in range(3):
        prev = t
        for a, b in MOJI.items():
            t = t.replace(a, b)
        if t == prev:
            break
    return t

def local_tag(tag):
    return tag.split("}", 1)[-1] if "}" in tag else tag

def elem_to_php_array(elem):
    children = list(elem)
    attrib = {local_tag(k): utf8_text(v) for k, v in elem.attrib.items()}
    if not children:
        text = utf8_text((elem.text or "").strip())
        if attrib:
            out = {"@attributes": attrib}
            if text != "":
                out["#text"] = text
            return out
        return text
    grouped = {}
    for child in children:
        key = local_tag(child.tag)
        grouped.setdefault(key, []).append(elem_to_php_array(child))
    out = {}
    if attrib:
        out["@attributes"] = attrib
    for key, vals in grouped.items():
        out[key] = vals[0] if len(vals) == 1 else vals
    return out

def parse_xml(path):
    raw = path.read_bytes()
    if raw.startswith(b"\xef\xbb\xbf"):
        raw = raw[3:]
    text = raw.decode("utf-8", errors="replace")
    return ET.fromstring(text)

def convert_styles(xml_path, json_path):
    root = parse_xml(xml_path)
    styles = []
    name = "RKRK Styles"
    for el in root.iter():
        tag = local_tag(el.tag)
        if tag == "name" and local_tag(el.getparent().tag if hasattr(el, "getparent") else "") == "Document":
            pass
    for el in root.iter():
        if local_tag(el.tag) == "Document":
            for child in el:
                if local_tag(child.tag) == "name":
                    name = utf8_text((child.text or "").strip()) or name
    for el in root.iter():
        if local_tag(el.tag) != "Style":
            continue
        data = elem_to_php_array(el)
        sid = ""
        if isinstance(data, dict) and "@attributes" in data:
            sid = data["@attributes"].get("id", "")
            body = {k: v for k, v in data.items() if k != "@attributes"}
        else:
            body = data if isinstance(data, dict) else {}
        styles.append({"id": sid, **body})
    payload = {
        "schema": "rkrk-kml-styles",
        "version": 1,
        "root": "kml",
        "name": name,
        "styles": styles,
    }
    json_path.parent.mkdir(parents=True, exist_ok=True)
    json_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    return len(styles)

def convert_summary(xml_path, json_path):
    root = parse_xml(xml_path)
    data = elem_to_php_array(root)
    if isinstance(data, dict) and "@attributes" in data:
        data = {k: v for k, v in data.items() if k != "@attributes"}
    payload = {
        "schema": "rkrk-summary-flights",
        "version": 1,
        "root": "Statistics",
        "data": data,
    }
    json_path.parent.mkdir(parents=True, exist_ok=True)
    json_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    keys = list(data.keys()) if isinstance(data, dict) else []
    return len(keys)

def main():
    base = Path(r"\\tchr-frog.synology.me@SSL@5606\DavWWWRoot\web\rkropen")
    xml_dir = base / "Missionen" / "xmlfiles"
    json_dir = base / "rkrk" / "webdaten"
    pairs = [
        ("RKRK_ALLKMLStyles.xml", convert_styles),
        ("RKRK_SummaryFluege.xml", convert_summary),
    ]
    if not xml_dir.is_dir():
        print("SKIP no xmlfiles: Missionen")
        return
    for xml_name, conv in pairs:
        src = xml_dir / xml_name
        dst = json_dir / xml_name.replace(".xml", ".json")
        if not src.is_file():
            print(f"MISS Missionen/{xml_name}")
            continue
        n = conv(src, dst)
        print(f"OK rkrk/webdaten/{dst.name} items={n} bytes={dst.stat().st_size}")

if __name__ == "__main__":
    main()
