#!/bin/bash

# Ajoute "enable_trusted_host_check=0" après la ligne "trusted_hosts[] = "localhost""
sed -i '/trusted_hosts\[\] = "localhost"/a enable_trusted_host_check=0' /var/www/html/config/config.ini.php

# Remplace "trusted_hosts[] = "localhost"" par "trusted_hosts[] = "localhost:8083""
sed -i 's/trusted_hosts\[\] = "localhost"/trusted_hosts[] = "localhost:8083"/' /var/www/html/config/config.ini.php
