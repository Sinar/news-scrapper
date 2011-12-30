# What does it do?

Pokes the following news sites, parses the content, tags it for use in relevant pages:

* The Malaysian Insider
* The Star Online
* The Malay Mail
* Utusan Malaysia (you really should look at their HTML)
* Merdeka Review (Malay language version)
* Free Malaysia Kini

# Dependencies

* PHP 5.3.x (with command line support)
* SQLite3 + relevant PHP bindings
* A good sense of humour

# How do I use it?

````bash
$ php -q scrapper.php
````

Add this to cron to some suitable timing.
