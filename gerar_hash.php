<?php
//esse ficheiro vai ajudar você a converter e suas senhas para inserir o hash no seu banco de dados
echo password_hash('admin123', PASSWORD_BCRYPT);