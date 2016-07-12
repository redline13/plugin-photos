<?php

require_once( 'LoadTestingTest.class.php' );

/**
 * PhotoUpload Plugin and Custom Test
 */
class UploadTest extends LoadTestingTest
{
	// Helper for deleting files.
	private function deleteFileOnClose ( $filename )
	{
		register_shutdown_function(function($file) {
			if (file_exists($file))
				unlink($file);
		}, $filename);
	}

	/**
	 * Start test, don't forget we have access to
	 * $this->iniSettings = all config data for test
	 * $this->testNum = test #
	 * $this->session = Run test with RedLine13 CURL wrapper and maintain cookies for user session.
	 */
	public function startTest()
	{
		// Tell Session object not to parse the responses.
		$this->disableResourceLoading();

		// Build the upload form data.
		$form = [];

		// Track time
		$startTime = time();

		// Get info to build request.
		$c = $this->iniSettings;

		// Test Basics
		$count = $c['photos_iterations'];
		$minDelay = $c['photos_mindelay'];
		$maxDelay = $c['photos_maxdelay'];

		// Image Basics
		$w = $c['photos_width'];
		$h = $c['photos_height'];
		$f = $c['photos_format'];

		// S3 Connection Data
		$s3url = $c['photos_s3url'];

		// Fill the form with META fields.
		$userPrefix = 'user-';
		$testPrefix = 'test-';
		if (!empty($c['photos_meta'])){
			$k = explode(',',$c['photos_meta']);
			foreach($k as $key){
				$v = explode(':',$key);
				$form["x-amz-meta-{$v[0]}"] = empty($v[1])?'':$v[1];
				if ( $v[0] == "testid" && !empty($v[1]) ){
					$testPrefix = $v[1];
				} else if ( $v[0] == "user" && !empty($v[1]) ){
					$userPrefix = $v[1];
				}
			}
		}

		// Fill out S3 Required Form Data. TODO: Remove S3 Requirement, support any photo upload.
		$form += [
			'acl' => 'public-read',
			'key' => 'original/${filename}',
			'X-Amz-Credential' => $c['photos_s3cred'],
			'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
			'X-Amz-Date' => $c['photos_s3date'],
			'Policy' => $c['photos_s3policy'],
			'X-Amz-Signature' => $c['photos_s3sig'],
			'X-Amz-Security-Token' => $c['photos_s3token'],
		];
		$headers = [];

		// Create selection of image files to use.
		$images = [];
		for( $i = 0; $i < 10; $i++ ){
			$imageStart = time();
			$u = uniqid();
			$tmpFilename = sys_get_temp_dir() . "/image-{$this->testNum}-$u.$f" ;
			$this->deleteFileOnClose($tmpFilename);

			/* Create a new imagick object */
			$im = new Imagick();
			$im->newPseudoImage($w, $h, 'plasma:fractal' );
			// $im->newImage($w, $h, new ImagickPixel('white'));
			$im->addNoiseImage(Imagick::NOISE_IMPULSE );
			$im->setImageFormat($f);
			$im->writeImage($tmpFilename);
			$images[] = $tmpFilename;

			$endTime = time();
			$totalTime = $endTime - $imageStart;
			recordURLPageLoad( "createImage", $endTime, $totalTime, false, filesize($tmpFilename));
		}

		// For all the photos we are asked to upload, get to it!
		$error = false;
		for( $i = 0; $i < $count; $i++ ){
			try {
				$image = $images[$i%10];
				$name = uniqid("a") . "-" . basename($image);
				$cfile = curl_file_create( $image, 'image/'.$f, $name ); // [, $mimetype, $postname])
				if ( !empty($form['x-amz-meta-filename']))
					$form['x-amz-meta-filename'] = $image;
				if ( !empty($form['x-amz-meta-user']))
					$form['x-amz-meta-user'] = $userPrefix .$this->testNum;
				if ( !empty($form['x-amz-meta-testid']))
					$form['x-amz-meta-testid'] = $testPrefix . $i;
				// Set the file object on the upload.
				$form['file'] = $cfile;
				$this->session->goToUrl( $s3url, $form, $headers , true, false );
				$sleep = rand($minDelay,$maxDelay);
				usleep($sleep*1000);
			} catch( Exception $e ){
				if ( !empty($e->content) ){
					$xml = simplexml_load_string($e->content);
					$code = $xml->Code;
					$msg = $xml->Message;
					recordError( "Page Error $code : $msg" );
					if ( $code == 'InternalError' || $code == 'RequestTimeout' ){
						continue;
					}
				}
				$error = true;
				break;
			}
		}

		$endTime = time();
		$totalTime = $endTime - $startTime;
		recordPageTime($endTime, $totalTime, $error, 0);

		return true;
	}
}
