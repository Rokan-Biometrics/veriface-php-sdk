# VeriFace PHP SDK
Implements current VeriFace API

## Requirements
- PHP 7.4 or newer
- ext-curl
- ext-json

## Installation
Install the package via composer.

```bash
composer require veriface/veriface
````

Installation using archive:
- Download and extract archive file
- Include `VeriFace.autoload.php` to your project

## Contributing
Please contact us: https://veriface.eu/contact/

## Usage

### Basic initialization
```php
$vf = veriface\VeriFace::byApiKey('INSERT_YOU_API_KEY');

//Will immediately die without exception on curl error, if false the veriface\VeriFaceApiException is thrown (default: false)
$vf->setDieOnCurlError(true);
```

### Create verification

```php
$created = $vf->createVerification('LINK_LONG');
$created->openCode;  //Redirect to verification application with the openCode parameter
$created->sessionId;  //Session ID of created verification
```

### Create verification with custom reference ID combined with email invitation
```php
$created = $vf->createVerification('INVITE_EMAIL', null, null, null, 'primary_id_from_your_system',
    'example@example.org', null, [new veriface\dto\ExtendedReferenceDto('CUSTOMER_ID', 'test')]);
```

### Find verification by reference
There could be multiple verifications with the same reference.
```php
$verifications = $vf->findVerificationsByExtendedReference('CUSTOMER_ID', 'test');
//or
$verifications = $vf->findVerificationsByReferenceId(primary_id_from_your_system);
//or
$verifications = $vf->findVerificationsByExtendedReference('PRIMARY', 'primary_id_from_your_system');
```

### Get verification details with localized indicators
```php
$vf = veriface\VeriFace::byApiKey('INSERT_YOU_API_KEY');
$verification = $vf->getVerification('SESSION_ID', 'en_GB');
```

### Process webhook event
```php
$eventResult = $vf->processVerificationWebhook(file_get_contents('php://input'));
$verification = $vf->getVerification($eventResult->sessionId, 'en_GB');
```

### Monitoring details
```php
$monitoringData = $vf->getVerificationMonitoringData('SESSION_ID');
```

## License
The MIT License (MIT). Please see [LICENSE](LICENSE.txt) for more information.
