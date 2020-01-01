<?php
  return [
    'extantion' => 'jpeg,jpg,png,bmp,svg,gif,pdf,doc,docx,xls,xlsx',
    'secret' => env('UPLOAD_SECRET', 'TZi+PA3dT8BR7yproQcqUryieefUbp3iedGQXCMvcSA='), //echo base64_encode(openssl_random_pseudo_bytes(32));
    'vi' => env('UPLOAD_SECRET', '+dOnDRg9kO8arkWlsLDyMQ=='), // echo base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC')));
    'route_group' => []
  ];