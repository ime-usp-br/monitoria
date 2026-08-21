<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Certificados de Monitoria
    |--------------------------------------------------------------------------
    |
    | Endereço de e-mail da Secretaria que deve ser notificada quando um aluno
    | solicita um certificado, para que a Secretaria encaminhe o documento ao
    | USP ASSINA para validação e posterior envio ao aluno.
    |
    */

    'secretaria_email' => env('CERTIFICATE_SECRETARIA_EMAIL', ''),
];
