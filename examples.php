<?php
ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

//include_once "vendor/autoload.php";
include_once "src/VeriFace.autoload.php";

use veriface\Dto\ExtendedReferenceDto;


$vf = veriface\VeriFace::byApiKey('INSERT_YOUR_API_KEY', null, true);
$vf->setDieOnCurlError(false);

//Create basic verification - minimalistic invocation
//$created = $vf->createVerification('LINK_LONG');

//Or invite by email, or use custom parameters according to the Integration manual
$created = $vf->createVerification('INVITE_EMAIL', null, null, null, "PRIMARY_TEST_ID",
    'client.test@veriface.eu', null, [new ExtendedReferenceDto('CUSTOMER_ID', 'test')]);

//Open verification app by redirect or in popup with following openCode (parameter oc)
echo '<h1>Open code for redirect</h1>';
echo $created->openCode;

echo '<hr />';

echo '<h1>Verification details</h1>';
//After the verification is finished or webhook is received (or whenever you need) you can get verification details with following method
//example of webhook processing $vf->processVerificationWebhook(file_get_contents("php://input"))->sessionId;
$verificationDetail = $vf->getVerification($created->sessionId);
echo '<pre style="width:100%; height: 200px; overflow: scroll">' . print_r($verificationDetail, true) . '</pre>';

echo '<hr />';

echo '<h1>Find by extended reference</h1>';
//Or you can find the verification by reference (possible multiple verifications will be returned)
$verifications = $vf->findVerificationsByExtendedReference('CUSTOMER_ID', 'test');
echo '<pre style="width:100%; height: 200px; overflow: scroll">' . print_r($verifications, true) . '</pre>';

