# Installation ILIAS 7.6 auf Ubuntu 20.04
In dieser Anleitung finden Sie eine Beispiel-Installation von ILIAS 7.X auf Basis von Ubuntu 20.04.3.

## Systemvoraussetzungen
Wir empfehlen für ILIAS ab Version 7 folgendes:

  * ein aktuelles Betriebssystem (wir empfehlen Ubuntu 20.04)
  * MySQL >=5.7 oder MariaDB >=10.2 (wir empfehlen MariaDB 10.5 oder höher)
  * PHP 7.4 (PHP 8 wird aktuell noch nicht unterstützt)
  * Apache 2.4.x mit `mod_php`
  * ImageMagick 6.8+
  * OpenJDK
  * zip, unzip
  * Node.js: 12 (LTS)
  * git
  * composer v2
  * einen aktuellen Browser (Microsoft Edge, Firefox, Chrome etc.)

## Hardware

Die Frage nach der richtigen Dimensionierung des Servers ist abhängig von der Anzahl gleichzeitiger Benutzer. ILIAS ist nicht nur eine einfache Website sondern eine interaktive Plattform, die durchaus mehr Leistung beanspruchen kann als übliche kleine Webanwendungen. Dies ist natürlich davon abhängig, wie intensiv Sie das System weiterentwickeln und welche Ressourcen Sie in ILIAS einbinden und den Benutzer bereitstellen.
Grundsätzlich empfehlen wir mindestens **2 Prozessoren** und **4 GB RAM**, sowie eine Interanbindung von mindestens **100 MBit/s**. Der Speicherplatz ist ebenfalls abhängig von den Inhalten, die Sie und Ihre Lehrkräfte online bereitstellen. Wir setzen grundlegend **25 GB freien Speicher** voraus für das Betriebssystem, ILIAS inkl. Basisinhalte und der Datenbank.
Zusätzlich empfehlen wir Ihnen (z.B. mit Hilfe eines Monitorings) regelmäßig zu prüfen, ob die bereitgestellten Ressourcen passend ausgelegt sind und ggf. im Laufe des Betriebs nach zu justieren.

## Referenz-System

Wir bieten ein Referenz-System an, welches mit folgenden Modulen installiert wurde (https://test7.ilias.de)

| Package        | Version                     |
|----------------|-----------------------------|
| Distribution   | Ubuntu 20.04.2 LTS          |
| MariaDB        | 10.3                        |
| PHP            | 7.2.34                      |
| Apache         | 2.4.41                      |
| zip            | 3.0                         |
| unzip          | 6.00                        |
| JDK            | 1.8.0_292                   |
| NodeJS         | v10.24.1                    |
| wkhtmltopdf    | 0.12.6                      |
| Ghostscript    | 9.50                        |
| Imagemagick    | 6.9.10-23 Q16               |



# Die Installation und Konfiguration des Servers

Der erste Schritt, nach der Installation von Ubuntu 20.04.3, ist die Installation und Konfiguration von Apache2 und MariaDB. Dazu nutzen wir folgenden Code:
```
apt install apache2 mariadb-server libapache2-mod-php7.4
```

ILIAS benötigt außerdem die folgenden PHP-Module:
```
apt install php7.4 php7.4-gd php7.4-xsl php7.4-ldap php7.4-xmlrpc php7.4-dev php7.4-curl php7.4-cli php7.4-common php7.4-soap php7.4-mbstring php7.4-intl php7.4-xml php7.4-zip php7.4-imagick php7.4-bcmath php7.4-gmp
```

Den Webserver können Sie bereits über die IP-Adresse (oder den DNS-Namen falls vorhanden) aufrufen. Wir empfehlen die Seite nur per HTTPS aufrufbar zu machen. Dazu aktivieren wir folgende Module und starten den Apache-Webserver im Anschluss neu:

**SSL und rewrite Modul aktivieren**
```
a2enmod ssl
a2enmod rewrite
```

**Ordner für ILIAS anlegen, Berechtigungen setzen, Apache Config anlegen und aktivieren**
```
mkdir /var/www/ilias
mkdir /var/www/files
mkdir /var/www/logs
chown -R www-data:www-data /var/www/
vi /etc/apache2/sites-available/ilias.conf
a2ensite ilias
```

**Inhalt der Datei: ilias.conf**
```
<VirtualHost *:443>
        DocumentRoot /var/www/ilias/
        ServerName yout-server.com

        SSLEngine On
        SSLCertificateFile      /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

        <Directory /var/www/ilias/>
                Require all granted
                AllowOverride All
                Options -Indexes +FollowSymLinks
        </Directory>

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        ErrorLog /var/log/apache2/error.log
        CustomLog /var/log/apache2/access.log combined
</VirtualHost>
```
Passen Sie hier bitte Ihren **ServerName** an sowie die **Zertifikats-Dateien**.

**Inhalt der Datei: 000-default.conf**
```
<VirtualHost *:80>
        RewriteEngine On
        RewriteCond %{HTTPS} off
        RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI}
</VirtualHost>
```
In dieser Datei erzwingen wir **HTTPS** anstelle von **HTTP**.

Wir starten den Webserver neu, damit alle Änderungen angewendet werden:
```
service apache2 restart
```

ILIAS benötigt gewissen Anpassungen in PHP, damit die Funktionalität gewährleistet wird und das System perfomant läuft. Dazu erzeugen wir folgende Datei:
```
vi /etc/php/7.4/mods-available/ilias.ini
```

**Inhalt der Datei: ilias.ini**
```
max_execution_time = 600
memory_limit = 512M
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
post_max_size = 256M
upload_max_filesize = 256M
session.gc_probability = 1
session.gc_divisor = 100
session.gc_maxlifetime = 14400
session.hash_function = 0
session.cookie_httponly = On
session.save_handler = files
session.cookie_secure = On
allow_url_fopen = 1
max_input_vars = 10000
```

Aktivieren der PHP-Konfiguration:
```
phpenmod ilias
service apache2 restart
```

Wir konfigurieren nun den Datenbank-Server, indem wir das **Secure-Installation-Script** ausführen, dass MySQL/MariaDB direkt mit liefern. Dadurch werden grundlegende Einstellungen und Kennwörter gesetzt für eine sichere Nutzung:
```
mysql_secure_installation
```

  * **Abfrage 1:** Setzen Sie bitte als erstes ein sicheres Root-Kennwort
  * **Abfrage 2:** Den anonymen Benutzer entfernen? **JA**
  * **Abfrage 3:** Remote-Root-Login unterbinden? **JA**
  * **Abfrage 4:** Test-Datenbank entfernen? **JA**
  * **Abfrage 5:** Berechtigungen neu laden? **JA**

Wir legen nun eine Config-Datei für MySQL/MariaDB an, in der wir unsere ILIAS-Konfiguration eintragen.
```
/etc/mysql/mariadb.conf.d/99-ilias.cnf
```
In dieser Datei legen Sie bitte nach Ihren Anforderungen folgende Config-Parameter fest:
  * `query_cache_size` > 16M
  * `join_buffer_size` > 128.0K
  * `table_open_cache` > 400
  * `innodb_buffer_pool_size` > 2G (abhängig von Ihrer DB-Größe)

Wir empfehlen für die Datenbank (sind bei neuester DB-Version schon vorhanden):
  * InnoDB storage engine
  * Character Set: `utf8`
  * Collation: `utf8_general_ci`
  * **Hinweis:** Sie können mit dem Perl-Script MySQL-Tuner die Performance analysieren und die Datenbank entsprechend optimieren

Wir erstellen nun eine Datenbank für ILIAS mit folgenden Befehlen (Das Root-Passwort haben Sie eben im Secure-Installation-Prozess definiert): 
```
mysql -u root -p
CREATE DATABASE ilias CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'ilias'@'localhost' IDENTIFIED BY 'password';
GRANT LOCK TABLES on *.* TO 'ilias'@'localhost';
GRANT ALL PRIVILEGES ON ilias.* TO 'ilias'@'localhost';
FLUSH PRIVILEGES;
```

### Zusätzliche Module
Installieren Sie nun folgende Komponenten nach für den Einsatz von ILIAS:
```
apt-get install zip unzip imagemagick openjdk-13-jdk composer
```


# Die Installationvon ILIAS 7.6
Wechseln Sie zunächst in das ILIAS-Verzeichnis unter /var/www/ilias und holen Sie mit GIT die ILIAS-Installation:
```
cd /var/www/ilias
git clone https://github.com/ILIAS-eLearning/ILIAS.git . --single-branch
git checkout release_X
```
Alternativ können Sie die ZIP-Datei von der offiziellen ILIAS-Website herunterladen und manuell entpacken im ILIAS-Verzeichnis.

Wir laden nun alle nötigen Abhängigkeiten mit Composer nach:
```
composer install --no-dev
```

Setzen Sie nun die passenden Berechtigungen auf die Dateien und Order:
```
chown -R www-data:www-data /var/www/
```

Nun haben wir alle nötigen Daten auf dem Server und können die Installation von ILIAS mit dem [ILIAS Command-Line-Setup](../../setup/README.md)  durchführen durchführen.

Legen Sie dazu eine [minimal-config.json](../../setup/minimal-config.json) **außerhalb** des DocumentRoots (z. B. /var/www/minimal-config.json) an mit folgenden Parametern.
Alle möglichen Paramter finden Sie hier: [Liste der möglichen Parametern](../../setup/README.md#about-the-config-file)

**Beispiel-Konfigration**
```
{
    "common" : {
        "client_id" : "myilias"
    },
    "database" : {
        "user" : "ilias_user",
        "password" : "my_password"
    },
    "filesystem" : {
        "data_dir" : "/var/www/files"
    },
    "http" : {
        "path" : "http://www.your-server.de"
    },
    "language" : {
        "default_language" : "de",
        "install_languages" : ["de"]
    },
    "logging" : {
        "enable" : true,
        "path_to_logfile" : "/var/www/logs/ilias.log",
        "errorlog_dir" : "/var/www/logs/"
    },
    "systemfolder" : {
        "contact" : {
            "firstname" : "Max",
            "lastname" : "Mustermann",
            "email" : "max.mustermann@ilias.de"
        }
    },
    "utilities" : {
        "path_to_convert" : "/usr/bin/convert",
        "path_to_zip" : "/usr/bin/zip",
        "path_to_unzip" : "/usr/bin/unzip"
    }
}
```

Starten Sie nun die Installation zusammen mit unserer Config-Datei:
```
php7.4 setup/setup.php install /var/www/minimal-config.json
```







