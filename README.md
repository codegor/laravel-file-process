# laravel-file-process
A laravel plugin for receive base64 file, store and generate url. Then get the file from the url.

# How to install?

1. Install File API

    ```php
    composer require 'codegor/laravel-file-process'
    ```

1. publish config file

    ```php
    php artisan vendor:publish --provider="Codegor\Upload\Providers\UploadServiceProvider" --tag=config
    ```
    
# How to config?

in `config/upload.php`

1. fill in the extansion of acepted files.

    ```php
    'extantion' => 'jpeg,jpg,png,bmp,svg,gif,pdf,doc,docx,xls,xlsx',
    ```
1. fill in the secret for url encode (.env or config/upload.php).

    .env
    ```php
    UPLOAD_SECRET=TZi+PA3dT8BR7yproQcqUryieefUbp3iedGQXCMvcSA=
    UPLOAD_VI=+dOnDRg9kO8arkWlsLDyMQ==
    ```
    You can regenerate it paste to [PHP Tester](http://phptester.net/) this code:
    ```php
    <?php
    echo 'UPLOAD_SECRET='.base64_encode(openssl_random_pseudo_bytes(32));
    echo PHP_EOL;
    echo 'UPLOAD_VI='.base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC')));
    ```
1. fill in the route group for grop of route file - (def route path is '/file/{hash}')
   ```php
   'route_group' => [... your group route config],
   ```

# How to use?

For save file from a client (file in base64 encoding) do this:

```php
$url = \Codegor\Upload\Store::uploadFileRndName($extentionOfFile, $base64DataOfFile); 
// $url = '/file/xozYGselci9i70cTdmpvWkrYvGN9AmA7djc5eOcFoAM='
```

If you try to view from the browser the $url you will see it, becouse route and contoller present on this library.

**Store path:** {storege}/app/file/{user_id}[/0..xx?]/{hash}.{ext}

More informayion you can see at code (folder src)
