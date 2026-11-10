FROM postgres:18.6

# Dépendances nécessaires et PostGIS
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        postgresql-18-postgis-3 \
        postgresql-18-postgis-3-scripts \
        ca-certificates \
        curl \
    && rm -rf /var/lib/apt/lists/*

# Dépôt officiel Dalibo
RUN curl -fsSL https://repo.dalibo.com/install.sh \
        -o /tmp/install-dalibo.sh \
    && sh /tmp/install-dalibo.sh \
    && rm /tmp/install-dalibo.sh

# PostgreSQL Anonymizer pour PostgreSQL 18
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        postgresql_anonymizer_18 \
    && dpkg-query -W -f='${Version}\n' postgresql_anonymizer_18 \
        | awk -F'[-+~:]' '{ if ($1+0 < 3 || ($1+0 == 3 && $2+0 < 3)) exit 1 }' \
    && rm -rf /var/lib/apt/lists/*
