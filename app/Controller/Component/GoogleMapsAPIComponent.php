<?php
class FileUploadComponent extends Component {

	public function getTransferingTime($startLatitude, $startlongitude, $DestinationLatitude , $DestinationLongitude){

		$GoogleMapsApiUrl ='http://maps.googleapis.com/maps/api/distancematrix/xml?origins='.$startLatitude.','.$startlongitude.'&destinations='.$DestinationLatitude.','.$DestinationLongitude.'&mode=walk&language=ja&sensor=false';
		$foo = simplexml_load_file($GoogleMapsApiUrl);
		return $foo;
	}
}