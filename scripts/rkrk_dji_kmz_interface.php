<?php
// DJI KMZ Interface
// https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/overview.html
// https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/common-element.html
// Last update 09.05.2025

	$DroneSettings = Array(
					"3T" => array(
						"Name" => "Mavic 3T",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "mavic3T",
						"DFOV" => 61,
						"IRCamAngle_H" => 49,  //(vorher 48)
						"IRCamAngle_V" => 40, //(vorher 38)
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "M3T.png",
						"conversion" => true,
						"external" => false,
						"advanced" => true,
						"editor" => true,
					),
					"D" => array(
						"Name" => "Mavic 3T - Debug",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "mavic3T",
						"DFOV" => 61,
						"IRCamAngle_H" => 49,
						"IRCamAngle_V" => 40,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "M3T.png",
						"conversion" => true,
						"external" => false,
						"advanced" => true,
						"editor" => true,
					),
					"3TD" => array(
						"Name" => "Mavic 3TD",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice3TD",
						"DFOV" => 61,
						"IRCamAngle_H" => 49, //(vorher 48)
						"IRCamAngle_V" => 40, //(vorher 38)
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "",
						"conversion" => true,
						"external" => false,
						"advanced" => true,
						"editor" => true,
						),
					"3TA" => array(
						"Name" => "Mavic 3TA",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice3TA",
						"DFOV" => 41.2,
						"IRCamAngle_H" => 33,
						"IRCamAngle_V" => 25.5, 
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "",
						"conversion" => true,
						"external" => false,
						"advanced" => true,
						"editor" => true,
						),
					"4T" => array(
						"Name" => "Matrice 4T",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice4T",
						"DFOV" => 45,
						"IRCamAngle_H" => 36,
						"IRCamAngle_V" => 29,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "M4T.png",
						"conversion" => true,
						"external" => false,
						"advanced" => true,
						"editor" => true,
					),	
					"4TD" => array(
						"Name" => "Matrice 4TD",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice4TD",
						"DFOV" => 45,
						"IRCamAngle_H" => 36,
						"IRCamAngle_V" => 29,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "M4TD.png",
						"img" => "M4T.png",
						"conversion" => true,
						"external" => false,
						"advanced" => true,
						"editor" => true,
					),	
					"30T" => array(
						"Name" => "Matrice 30T",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice30T",
						"DFOV" => 61,
						"IRCamAngle_H" => 49, //(vorher 48)
						"IRCamAngle_V" => 34, //(vorher 38)
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "M30T.png",
						"conversion" => false,
						"external" => false,
						"advanced" => true,
						"editor" => false,
					),	
					"M2EA" => array(
						"Name" => "Mavic 2EA",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "mavic2EnterpriseAdvanced",
						"DFOV" => 61, // ~61° berechnet
						"IRCamAngle_H" => 48,
						"IRCamAngle_V" => 34,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "M2AE.jpg",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
					),	
					"Zenmuse H20T" => array( // Matrice 300 H20T
						"Name" => "Zenmuse H20T",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice300",
						"DFOV" => 40.6,
						"IRCamAngle_H" => round(40.6*0.8,0), //berechnet
						"IRCamAngle_V" => round(40.6*0.55,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "ZH20T.png",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
						),
					"Zenmuse H30T" => array(  //	 "Matrice 350 H30T",
						"Name" => "Zenmuse H30T",
						"UAVCopterProfile" => "dji-pilot-2",
						"UAVDroneType" => "matrice300",
						"DFOV" => 45.2,
						"IRCamAngle_H" => round(45.2*0.8,0), //berechnet
						"IRCamAngle_V" => round(45.2*0.55,0), //berechnet
						"IRCamResolution_H" => 1280,
						"IRCamResolution_V" => 1024,
						"img" => "ZH30T.png",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
						),
					"FB320 34°" => array(
						"Name" => "FB320 34°",
						"UAVCopterProfile" => "dji",
						"UAVDroneType" => "other",
						"DFOV" => 0, // ~42.8° berechnet
						"IRCamAngle_H" => 34,
						"IRCamAngle_V" => round(34*0.8,0),
						"IRCamResolution_H" => 320,
						"IRCamResolution_V" => 256,
						"img" => "flir_wb320.jpg",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
						),
					"FB320 50°" => array(
						"Name" => "FB320 50°",
						"UAVCopterProfile" => "dji",
						"UAVDroneType" => "other",
						"DFOV" => 0, // ~63° berechnet
						"IRCamAngle_H" => 50,
						"IRCamAngle_V" => 40,
						"IRCamResolution_H" => 320,
						"IRCamResolution_V" => 256,
						"img" => "flir_wb320.jpg",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
						),
					"FB640" => array(
						"Name" => "Mavic 2 zoom, Bambikit 640",
						"UAVCopterProfile" => "dji",
						"UAVDroneType" => "other",
						"DFOV" => 0, // ~40.4° berechnet
						"IRCamAngle_H" => 32,
						"IRCamAngle_V" => 0,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "flir_wb640.jpg",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
						),
					"XT384" => array(
						"Name" => "Mavic 2 zoom, Bambikit XT384",
						"UAVCopterProfile" => "dji",
						"UAVDroneType" => "other",
						"DFOV" => 0, // ~35° berechnet
						"IRCamAngle_H" => 28,
						"IRCamAngle_V" => 0,
						"IRCamResolution_H" => 384,
						"IRCamResolution_V" => 288,
						"img" => "UAV-XT384.jpg",
						"conversion" => false,
						"external" => true,
						"advanced" => true,
						"editor" => false,
						),
	); 
		
	$DJI_DroneInformation = Array(
		//https://developer.dji.com/doc/cloud-api-tutorial/en/overview/product-support.html
		//DroneType
		"wpml:droneEnumValue" => Array (
									"3T" => 77,
									"3TA" => 77,
									"4T" => 99,  
									"4TD" => 100,
									"30T" => 67,
									"3TD" => 91,
									"350" => 89,
									"300" => 60,
										),
		"wpml:droneSubEnumValue" => Array (
									"3T" => 1,
									"3TA" => 3,
									"4T" => 1,
									"4TD" => 1,
									"30T" => 1,
									"3TD" => 1,
									"350" => 0,
									"300" => 0,
										),
		//Payload
		"wpml:payloadEnumValue" => Array (
									"3T" => 67,
									"3TA" => 129,
									"4T" => 89,
									"4TD" => 99,
									"30T" => 53,
									"3TD" => 81,
									"Z_H20T" => 43,
									"Z_H30T" => 83,
										),
		"wpml:payloadSubEnumValue" => Array (
									"3T" => 0,
									"3TA" => 0,
									"4T" => 0,
									"4TD" => 0,
									"30T" => 0,
									"3TD" => 0,
									"Z_H20T" => 0,
									"Z_H30T" => 0,
										),
		"wpml:payloadPositionIndex" => Array (
									"3T" => 0,
									"3TA" => 0,
									"4T" => 0,
									"4TD" => 0,
									"30T" => 0,
									"3TD" => 0,
									"Z_H20T" => 0,
									"Z_H30T" => 0,
										),
	);
	//https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/waylines-wpml.html
	//https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/template-kml.html
	$DJI_Commands = Array(
		"wpml:finishAction" => Array ("goHome","noAction","gotoFirstWaypoint"),
		// goHome: After the aircraft completes the waylines mission, exit the waylines mode and go home.
		// noAction: After the aircraft completes the waylines mission, exit the waylines mode.
		// autoLand: After the aircraft completes the waylines mission, exit the waylines mode and land on the spot.
		// gotoFirstWaypoint: After the aircraft completes the waylines mission, it immediately flies to the first point of the waylines, and exits the waylines mode when it arrives.
		
		"wpml:exitOnRCLost" => Array ("goContinue","executeLostAction"),
		// goContinue
		// executeLostAction: Exit the waylines mode and execute a runaway action.
		
		"wpml:executeRCLostAction" => Array ("goBack","landing","hover"),
		//goBack: The aircraft flies from the out-of-control position to the take-off point. 
		//landing: The aircraft landed on the spot in an out-of-control position. 
		// hover: The aircraft is hovering in an out-of-control position.
		
		"wpml:flyToWaylineMode" => Array ("safely","pointToPoint"),
		//safely: (M300)The aircraft takes off, rises to the altitude of the first waypoint, and then flies level to the first waypoint. If the first waypoint is lower than the take-off point, after takeoff, it will level fly to the top of the first waypoint and then descend.
		//(M30)The aircraft takes off, rises to the altitude of the first waypoint, and then flies level to the first waypoint. If the first waypoint is lower than the "safe take-off altitude", after taking off to the "safe take-off altitude", level flight to the first waypoint and then descend. Note that the "safe takeoff altitude" only takes effect when the aircraft is not taking off.

		//pointToPoint
		//(M300)After the aircraft takes off, it tilts to the first waypoint.
		//(M30)The aircraft takes off to the "safe takeoff altitude", and then tilts to the first waypoint. If the altitude of the first waypoint is lower than the "safe take-off altitude", it will first level flight and then descend.
		
		"wpml:takeOffSecurityHeight" => Array (
									"3T" => Array("low" => 1.2,"high" => 1500),
									"3TA" => Array("low" => 1.2,"high" => 1500),
									"4T" => Array("low" => 1.2,"high" => 1500),
									"4TD" => Array("low" => 8,"high" => 1500),
									"30T" => Array("low" => 1.2,"high" => 1500),
											),
		//For the remote controller, the value range is [1.2,1500]. For the dock, the value range is [8,1500]. (height mode: height relative to take-off point)
		
		"wpml:globalTransitionalSpeed" => Array("low" => 0,"high" => 15),
		//Note: The speed at which the drone flies to the first waypoint of each waylines. The speed at which the drone resumes from its current position to the break point when the waylines mission is interrupted.

		"wpml:autoFlightSpeed" => Array (
									"3T" => Array("low" => 0,"high" => 21),
									"3TA" => Array("low" => 0,"high" => 21),
									"4T" => Array("low" => 0,"high" => 21),
									"4TD" => Array("low" => 0,"high" => 21),
									"30T" => Array("low" => 0,"high" => 23),
											),
		// (0, Maximum flight speed of the current drone]
		
		"wpml:waypointHeadingMode" => Array ("followWayline","manually","fixed","smoothTransition","towardPOI"),
		//followWayline:Along course direction. The nose of the aircraft follows the course direction to the next waypoint.
		//manually: The user can manually control the nose orientation of the aircraft during the flight to the next waypoint.
		//fixed: The nose of the aircraft maintains the yaw angle of the aircraft to the next waypoint after the waypoint action has been performed.
		//smoothTransition: Customized. The target yaw angle for a waypoint is given by "wpml:waypointHeadingAngle" and transitions evenly to the target yaw angle of the next waypoint during the flight segment.
		//towardPOI: The aircraft heading faces the point of interest.
				
		//https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/template-kml.html
		//https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/common-element.html
		
		"wpml:globalWaypointTurnMode" => Array ("coordinateTurn","toPointAndStopWithDiscontinuityCurvature","toPointAndStopWithContinuityCurvature", "toPointAndPassWithContinuityCurvature"),
		//coordinateTurn: Coordinate turn, but point, turn ahead
		//toPointAndStopWithDiscontinuityCurvature: Fly in a straight line, the aircraft will stop at the point
		//toPointAndStopWithContinuityCurvature: Curve flight, the aircraft will stop at the point
		//toPointAndPassWithContinuityCurvature: Curve flight, the aircraft will not stop at the point
		
		"wpml:globalUseStraightLine" => Array ("","",""),
		//0: The trajectory of the flight segment is a curve in the whole process
		//1: The trajectory of the flight segment should be as close as possible to the line connecting the two points
		
		"wpml:coordinateMode" => Array ("","",""),
		"wpml:heightMode" => Array ("","",""),
		"wpml:positioningType" => Array ("","",""),
		"wpml:gimbalPitchMode" => Array ("manual","usePointSetting"),
		//manual: Manual control. When the aircraft is flying from one waypoint to the next, the user can manually control the pitch angle of the gimbal; if there is no user control, the gimbal pitch angle when flying away from the waypoint is maintained.
		//usePointSetting: Set according to each waypoint. When the aircraft flies from one waypoint to the next, the pitch angle of the gimbal transitions evenly to the pitch angle of the next waypoint.
		
		"wpml:templateType" => Array ("waypoint","mapping2d","mapping3d","mappingStrip"),
		//Predefined Template Type
		//Note: Templates provide users with a solution for quickly generating routes. The user fills in the template Element and then imports the DJI support client (such as DJI Pilot) to quickly generate an executable mapping/inspection route.

		"wpml:templateId" => Array ("low" => 0,"high" =>65535),
		//Template ID
		//Note: This ID is unique within a kmz file. It is recommended to start monotonically and continuously increase from 0. In the template.kml and waylines.wpml files, this id will be used to associate the template with the generated executable routes.
		
		"wpml:useGlobalHeight" => Array (0,1),
		// 0: Do not use
		// 1: use
		//Global height of flight route(related to the height of takeoff point)
		
		"wpml:ellipsoidHeight" =>  "",
		// Waypoint height (WGS84 ellipsoid height)
		// Note: This element is used in conjunction with "WPML:height", which is an expression of the reference plane at the same position with different elevations.

		"wpml:height" =>  "",
		// Waypoint height(EGM96 altitude/relative to take-off /AGL relative to ground)
		//Note: This element is used in conjunction with "wpml:ellipsoidHeight", which is an expression of a different elevation reference plane at the same location.

		"wpml:useGlobalSpeed" => Array (0,1),
		// 0: Do not use
		// 1: use
		
		"wpml:waypointSpeed" => Array (
									"3T" => Array("low" => 0,"high" => 21),
									"3TA" => Array("low" => 0,"high" => 21),
									"4T" => Array("low" => 0,"high" => 21),
									"4TD" => Array("low" => 0,"high" => 21),
									"30T" => Array("low" => 0,"high" => 23),
											),
		// (0, Maximum flight speed of this drone]
		// Note: Maximum flight speed varies between drones
		// Note: Required if "wpml:useGlobalSpeed" is 0.

		"wpml:useGlobalHeadingParam" => Array (0,1),
		// 0: Do not use
		// 1: use

		"wpml:waypointHeadingParam" => "",
		//Note: Required if “wpml:useGlobalHeadingParam” is 0.
		
		"wpml:useGlobalTurnParam" => Array (0,1),
		// 0: Do not use
		// 1: use

		"wpml:waypointTurnParam" => "",
		//Note: Required if “wpml:useGlobalTurnParam” is 0.
		
		"wpml:waypointTurnDampingDist" => "",
		//0, the maximum length of wayline segment]
		// Note: The wayline segment between two waypoints should be greater than the sum of the turn intercepts of two waypoints. This element defines how far to the waypoint that the aircraft should turn.	Yes
		//Required if "wpml:waypointTurnMode" is "coordinateTurn", "wpml:waypointTurnMode" is "toPointAndPassWithContinuityCurvature" and "wpml:useStraightLine" is 1	M300 RTK, M350 RTK, M30/M30T, M3E/M3T/M3M, M3D/M3TD, M4E/M4T

		"wpml:waypointTurnMode" => Array ("coordinateTurn","toPointAndStopWithDiscontinuityCurvature","toPointAndStopWithContinuityCurvature","toPointAndPassWithContinuityCurvature"),
		// coordinateTurn: Coordinate turn, but point, turn ahead
		// toPointAndStopWithDiscontinuityCurvature: Fly in a straight line, the aircraft will stop at the point
		// toPointAndStopWithContinuityCurvature: Curve flight, the aircraft will stop at the point
		// toPointAndPassWithContinuityCurvature: Curve flight, the aircraft will not stop at the point

		"wpml:globalUseStraightLine" => Array (0,1),
		// 0: The trajectory of the flight segment is a curve in the whole process
		// 1: The trajectory of the flight segment should be as close as possible to the line connecting the two points
		// Note: Required if and only if "wpml:globalWaypointTurnMode" is set to "toPointAndStopWithContinuityCurvature" or "toPointAndPassWithContinuityCurvature". If the Element of a waypoint is additionally defined, the local definition will override the global definition.		

	);

?>