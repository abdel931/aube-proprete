<?php
session_start();
session_unset();
session_destroy();
header('Location: /aube-proprete/espace-client/login.html');
exit;