<?php
require_once('lti.php');

if (empty($_SESSION['lti'])) {
	echo 'Error: You do not have permission to visit this page.';
	die();
}

// Only return mark is scoring is enabled
if (!empty($_SESSION['lti']['lis_outcome_service_url'])) {
	// Give participation mark
	$student_grade = 1;

	$outcome_url = $_SESSION['lti']['lis_outcome_service_url'];
	$consumer_key = $_SESSION['lti']['oauth_consumer_key'];
	$consumer_secret = '123456';

	$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();

	$body = '<?xml version = "1.0" encoding = "UTF-8"?>
		<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
			<imsx_POXHeader>
				<imsx_POXRequestHeaderInfo>
					<imsx_version>V1.0</imsx_version>
					<imsx_messageIdentifier>' . $_SERVER['REQUEST_TIME'] . '</imsx_messageIdentifier>
				</imsx_POXRequestHeaderInfo>
			</imsx_POXHeader>
			<imsx_POXBody>
				<replaceResultRequest>
					<resultRecord>
						<sourcedGUID>
							<sourcedId>' . $_SESSION['lti']['lis_result_sourcedid'] . '</sourcedId>
						</sourcedGUID>
						<result>
							<resultScore>
								<language>en</language>
								<textString>' . $student_grade . '</textString>
							</resultScore>
						</result>
					</resultRecord>
				</replaceResultRequest>
			</imsx_POXBody>
		</imsx_POXEnvelopeRequest>';

	$hash = base64_encode(sha1($body, TRUE));
	$params = array('oauth_body_hash' => $hash);
	$token = '';
	$content_type = 'application/xml';

	$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
	$outcome_request = OAuthRequest::from_consumer_and_token($consumer, $token, 'POST', $outcome_url, $params);
	$outcome_request->sign_request($hmac_method, $consumer, $token);

	$header = $outcome_request->to_header();
	$header = $header . "\r\nContent-type: " . $content_type . "\r\n";
	$options = array(
		'http' => array(
			'method' => 'POST',
			'content' => $body,
			'header' => $header,
		),
	);

	$ctx = stream_context_create($options);
	$fp = @fopen($outcome_url, 'rb', FALSE, $ctx);
	$response = @stream_get_contents($fp);
}
?>