# battery.ebiene.de

Source Code von [battery.ebiene.de](https://battery.ebiene.de) Website und API.

`Battery Status` Progressive Web App für BMW i-Modelle ermittelt und zeigt Live-Informationen rund um den Fahrzeug-Akku. Die App bedient sich an gleicher Schnittstelle, die auch von der deutschsprachigen BMW ConnectedDrive Website verwendet wird. Für die Nutzung der Schnittstelle wird ein Bearer-Token benötigt, den die App von der BMW ConnectedDrive Website automatisch einholt.

Die Webseite nach Einrichtung im Smartphone-Browser aufrufen und zum Homescreen hinzufügen. Ab diesem Zeitpunkt lässt sich die Web App vom Homescreen heraus im Vollbildmodus starten.


<p align="center">
    <img src="https://raw.githubusercontent.com/sergejmueller/battery.ebiene.de/master/img/screenshot-1.png" width="360" height="740" alt="Battery Statusanzeige">
    <br>
    Battery Statusanzeige
</p>


### Warnung

Keine Garantie für Richtigkeit und Aktualität. Inbetriebnahme auf eigene Gefahr und Verantwortung. Implementierung ausschließlich zu Demozwecken.


### Voraussetzungen

* Apache-Webserver mit PHP
* BMW ConnectedDrive Zugangsdaten


### Installation (API)

1. Datei `.htaccess` nach Wünschen anpassen, insbesondere [Zeilen 20-21](https://github.com/sergejmueller/battery.ebiene.de/blob/master/.htaccess#L20-L21).
2. Datei `cache.json` im Ordner `api/` beschreibbar anlegen.
3. Datei `token.json` im Ordner `api/` beschreibbar anlegen.
4. Datei `auth.json` im Ordner `api/` mit BMW ConnectedDrive Zugangsdaten anlegen:

```json
{
    "username": "XYZ",
    "password": "XYZ",
    "vehicle": "XYZ"
}
```

| Feld       | Beschreibung                    |
| ---------- |:-------------------------------:|
| `username` | BMW ConnectedDrive Benutzername |
| `password` | BMW ConnectedDrive Passwort     |
| `vehicle`  | 17-stellige Fahrgestellnummer   |


### Installation (NOTIFY)

Auf Wunsch kann der aktuelle Ladestatus des Fahrzeugs beobachtet und bei Änderungen (Ladevorgang gestartet, Ladevorgang beendet) eine Benachrichtigung gesendet werden. Folgende Voraussetzungen sind zu erwarten:

1. [Cronjob](https://de.wikipedia.org/wiki/Cron) (serverseitig oder extern)
2. [Webhook(s)](https://de.wikipedia.org/wiki/WebHooks) für Slack und/oder IFTTT

##### Das Prinzip
Via Cronjob prüft ein Skript auf die Veränderung des Ladezustandes (`chargingSystemStatus`). Bei einer Änderung wird ein Webhook aufgerufen/kontaktiert. Der aufgerufene Webhook (Slack und/oder IFTTT) stößt eine Benachrichtigung auf der entsprechenden (Smartphone/Desktop) App aus.

##### Cronjob

Ziel-URL für den Cronjob: `https://battery.ebiene.de/notify`

##### Webhooks

1. Datei `webhooks.json` im Ordner `notify/` mit angepassten Webhook-URLs anlegen:
```json
{
    "ifttt": "https://maker.ifttt.com/trigger/EVENT/with/key/KEY",
    "slack": "https://hooks.slack.com/services/XXX/YYY/ZZZ"
}
```

##### IFTTT

Webhook-URL baut sich wie folgt zusammen: `https://maker.ifttt.com/trigger/EVENT/with/key/KEY`

Der Wert `EVENT` kommt aus dem Punkt `3`, der `KEY` aus `8` (siehe nachfolgende Einrichtung).

1. https://ifttt.com/my_applets → `New Applet`
2. `if this` → `Webhooks` → `Receive a web request`
3. `Event Name` → z.B. `i3_charging` → `Create trigger`
4. `then that` → `Notifications` → `Send a notification from the IFTTT app`
5. `Message` → `{{Value1}}` → `Create action`
6. Review and finish
7. Den Schalter auf `On` stellen, falls nicht geschehen
8. https://ifttt.com/maker_webhooks → `Documentation` → `Your key is`

##### Slack

[Incoming Webhooks für Slack](https://api.slack.com/incoming-webhooks)


### Sicherheit

Um Zugriffe auf sensible (JSON-)Dateien mit Zugangs- und Token-Daten zu unterbinden, *muss* in `.htaccess` folgender Code-Snippet aufgenommen werden (in der Installationsdatei `.htaccess` [bereits vorhanden](https://github.com/sergejmueller/battery.ebiene.de/blob/master/.htaccess#L33-L36)):

```apache
<FilesMatch "(^\.|\.(json|md)$)">
    order deny,allow
    deny from all
</FilesMatch>
```


### Datenausgabe

Nachfolgende Datenwerte zeigt die `Battery Status` Web App aktuell an:

* Charge Status (Prozent)
* Electric Range (Kilometer)
* Fully Charged (Reststunden)
* State of Charge (kWh)
* State of Charge Max (kWh)


### App-Icon

Von [Makeable](https://www.iconfinder.com/makea)
