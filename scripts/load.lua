-- disable OAuth signature checking by changing $built == $signature to true on line 60 in OAuth.php
-- and check_nonce and check_timestamp on line 593,594
math.randomseed(os.time())
request = function()
    local user = math.random(1000000)
    local body = "launch_presentation_return_url=&lti_version=LTI-1p0&user_id=" .. user .. "&roles=Student&oauth_nonce=183891289650998090291439835118&oauth_timestamp=1439835118&lis_result_sourcedid=UBCx%2FRMAP101%2F2015_T1%3Aedx.ctlt.ubc.ca-i4x-UBCx-RMAP101-lti-a675f61d7c1440a39c9fd18141c18baf%3A5197db5b1572c8c2e1ad1b64176dee2d&context_id=UBCx%2FRMAP101%2F2015_T1&oauth_consumer_key=rmap&resource_link_id=edx.ctlt.ubc.ca-i4x-UBCx-RMAP101-lti-a675f61d7c1440a39c9fd18141c18baf&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&lis_outcome_service_url=http%3A%2F%2Fedx.ctlt.ubc.ca%2Fcourses%2FUBCx%2FRMAP101%2F2015_T1%2Fxblock%2Fi4x%3A%3B_%3B_UBCx%3B_RMAP101%3B_lti%3B_a675f61d7c1440a39c9fd18141c18baf%2Fhandler_noauth%2Fgrade_handler&oauth_signature=sY8ilF%2BdwogdIb4ZCTWaqUcAM3Y%3D&lti_message_type=basic-lti-launch-request&oauth_callback=about%3Ablank"
    return wrk.format(nil, nil, nil, body)
end
wrk.method = "POST"
wrk.headers["Content-Type"] = "application/x-www-form-urlencoded"
