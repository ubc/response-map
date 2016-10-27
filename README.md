# Response Map

The response map is an LTI tool that allows students to respond to a question or give feedback and have the responses show up on a world map based on the location that they enter in. All the response are also processed and turned into a word cloud at the bottom of the map. Students can also upload an image along with their response.

## Requirements
You will need have an Apache HTTP server which is configured to serve PHP files and have a MySQL database configured to store student details and responses.

### Dependencies
- PHP 5.3+ (tested with 5.4 and 5.5)
- PHP extensions: mysqli, gd, imagick

## Installation
1. Enable a Google Geocoding API. Refer to [Google's instructions](https://developers.google.com/maps/documentation/geocoding/#api_key) for details.
2. Set the configuration variables required (MySQL credentials, Google Geocoding API key, LTI key and secret) in one of the two methods below:
    - Set them as environment variables. Refer to `configuration.php` for variable names.
    - Save a copy of `config.example.php` as `config.php` and edit the configuration variables.
3. Create a database with the name set in Step 2 and import `response_map.sql` to create the necessary tables.

## Integrating with edX
1. [Enable LTI component for your course](http://edx.readthedocs.io/projects/edx-partner-course-staff/en/latest/exercises_tools/lti_component.html#enabling-lti-components-for-a-course).
2. Also under Advanced Settings, the LTI Passports array must contain the LTI key and secret pair that is used by the tool (set in Step 2). You must add it to the array in the following format: ```"passport_id:key:secret"```. The id is later used when configuring the LTI component to obtain the key and secret.
3. Next, create the LTI component within a course unit (under Add New Component > Advanced > LTI) and click on "Edit". Make sure to enter in the the LTI ID that you have previously set in LTI Passport. Specify the url to the tool (make sure you have a closing slash) and turn off opening in a new page for a seamless look. If you would like to give a student a partipation mark for responding to the response-map, then set the "Scored" attribute to true.

Notes
- If your edX instance is using http instead of https. Add `HTTPS: "off"` to `lms.envs.json` and restart the server to get the grading functionality to work. The reason is even though edX may be on http the outcome url passed to Response Map uses https.
- Currently, there is a limitation of only having one map per subsection.

## Integrating with other Platforms
Please refer to the your Platform's LTI integration instructions.

## Custom Parameters
You can use the custom parameters below to customize parts of the tool. To do so click on "Edit" and add custom parameters in the following format: `parameter=value`.

### Word Cloud
A word cloud of the keywords in all the responses

| Parameter | Value | Description |
|-----------|-------|-------------|
| showcloud | true  | to have the word cloud appear below the map |
| usecolor  | true  | to have the word cloud in color instead of black and white |
### Form Labels
Names for the form fields. For example, we want the location field to be label "Place", set the custom parameter as `location_label=Place`

| Parameter  | Description | Default (if parameter not set) |
|------------|-------------|--------------------------------|
| head_label | for the first text field of the response form | Name |
| location_label | for the location field | Location
| response_label | for the response text area | Response |

## Workflow
<img src="https://github.com/UQ-UQx/response-map/blob/master/README_WORKFLOW_IMAGE.png?raw=true">

##License
This project is licensed under the terms of the MIT license.

##Contact
The best contact point apart from opening github issues or comments is to email technical@uqx.uq.edu.au
