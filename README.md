# letsencrypt-wedos
Skripty pro validaci přes DNS záznamy (metoda DNS-01) vedené u Wedosu umožňující validaci wildcard certifikátů.

## Požadavky
* PHP 5.4 - 7+ CLI (curl, json)
* aktuální certbot na Linuxu, nastavený server acme-v02
* DNS záznamy hostované u Wedosu
* u Wedosu zapnuté rozhraní WAPI

Certbot při generování wildcard certifikátů automaticky volí metodu validace DNS-01. Také však pro úplný DNS záznam potřebuje mít funkční metodu validace HTTP-01.

Tato sada skriptů podporuje oboje. Vzhledem k časové náročnosti replikace změn DNS zázamů je potřeba čekat na dokončení přidání DNS záznamu, skript čeká dokud nejsou viditelné, maximálně však 20 minut. Po validaci certbot zahájí čištění DNS záznamu a volá ho se stejnými parametry, tedy je po použití vymazán jen záznam, který tam byl vložen.


## Instalace
* nahrajte obsah do složky _/etc/letsencrypt/scripts/_
* nastavte oprávnění +x souborům _authenticator.php_ a _cleanup.php_
* ve složce http01 zkopírujte _config.php.dist_ do _config.php_ a do jeho obsahu nastavte webroot
* ve složce wedos-dns01 zkopírujte _config.php.dist_ do _config.php_ a do jeho obsahu vložte váš přihlašovací e-mail a WAPI heslo
* nastavte _/etc/letsencrypt/cli.ini_ dle konfigurace níže:
* otestujte: _certbot certonly -d domena -d *.domena_

## Ukázková konfigurace cli.ini
```ini
server = https://acme-v02.api.letsencrypt.org/directory
# email = 
agree-tos = true
# renew-hook = /bin/run-parts /etc/letsencrypt/renew-hook.d/

authenticator = manual
manual-auth-hook = /etc/letsencrypt/scripts/authenticator.php
manual-cleanup-hook = /etc/letsencrypt/scripts/cleanup.php
manual-public-ip-logging-ok = true
```
