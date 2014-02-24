#### Copyrights

Copyright (C) 2013-2014 T Dispatch Ltd

    T Dispatch Ltd
    35 Paul Street
    London
    UK EC2A 4UQ

For more details visit www.tdispatch.com

#### Trademarks

T Dispatch logo, T Dispatch, the T Dispatch T shaped logo are all trademarks of T Dispatch Ltd.

## Requirements

Braintree wrapper requires PHP version `5.2.1` or better. The following PHP extensions are also required:

    * curl
    * dom
    * hash
    * openssl
    * SimpleXML
    * xmlwriter

## Configuration

The only file you need to edit to configure this wrapper script is `access.php` (copy/rename `access-dist.php` template file to create your `access.php`). You must put your Braintree merchantId, public and private key as well as your keys to T Dispatch API as obtained from T Dispatch customer support, for example:

```php
  define('TD_FLEET_API_KEY'  , 'df60bd52hd83zzj47dgjsi937c710c3e');
  define('TD_API_CLIENT_ID'  , 'T546TYNxja@tdispatch.com');
  define('TD_API_SECRET'     , 'sifPDS4QNNTkix72JXLTHYNA9ikjd0XP');
 
  define('BT_ENV'            , 'production');
  define('BT_MERCHANT_ID'    , 'xrtnxytj8ygs723434');
  define('BT_PUBLIC_KEY'     , '8nxeft93gdehdfyws26');
  define('BT_PRIVATE_KEY'    , '4hyuy6hsd9ihtnxlsg98ypnsxiypophd');
```

#### Enabling wrapper access to API

Before you start using this wrapper you must connect it with API and grant it access to your cab office. To do so use any web browser (i.e. or tools like `wget`, `curl`) and open the following URL (we assume that this script is installed on your webserver, available under `YOUR-DOMAIN` domain name, and was placed in `braintree` subfolder):

    https://YOUR-DOMAIN/braintree/init.php
    
You should see message like:

    API response: Authorization is waiting for your acceptance [#400]
    
Now you need to log in to T Dispatch controller panel, and go to: `https://app.tdispatch.com/preferences/fleet-api/` where you should see all applications that are either connected to your cab office, or are willing to be connected, like this script. You how need to check if your wrapper script is correctly listed with `scope` equal to `update-payment` and then clikc `Approve` to grant it access. You can verify if all is fine, by accessing `init.php` script again. This time you should see:

    API response: OK, your are ready to go now.
    
If so, you should remove `init.php` script from your server and  you are done and ready to accept payments from passenger apps.

#### License

 Copyright (C) 2013-2014 T Dispatch Ltd

 See the LICENSE for terms and conditions of use, modification and distribution

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.

