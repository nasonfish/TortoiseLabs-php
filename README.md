## TortoiseLabs-php

*TortoiseLabs-php* is a small and simple framework for accessing [TortoiseLabs](http://tortois.es/)' API. TortoiseLabs-php is made with only one file, and should be easy to use for controlling your account through the API.

### How do I use it?

Simply copy the TortoiseLabs.php file somewhere and include/require it.

```php
include 'tortoiselabs-php/TortoiseLabs.php'
```

Then, to use the API:
```php
$tl = new TortoiseLabs('your_username', 'MyAwesomeAPIKeyIGotFromMyProfileonManage.Tortois.es');
```

And use your TortoiseLabs object to call functions!

This API has four separate classes that sit in TortoiseLabs.php, each allowing access to a different part of the API.

 - `$tl->vps`
 - `$tl->support`
 - `$tl->invoice`
 - `$tl->dns`

Inside these classes, you can call functions listed in the [API](http://wiki.tortois.es/index/API), and we'll return the array listed in the API.

### Examples

```php
$tl = new TortoiseLabs('nasonfish', '12345');
var_dump($data = $tl->vps->list_all()); // All my information about my VPS'
$id = $data['vpslist'][0]['id'];
$tl->vps->setNickname($id, 'Personal_VPS');
```
