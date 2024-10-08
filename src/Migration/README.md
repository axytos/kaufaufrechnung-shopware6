# Create Migrations
1. run  `bin/console database:create-migration -p AxytosKaufAufRechnung --name <MigrationName>`   to generate new migration class
2. run  `bin/console dal:create:schema`                                                           to generate sql create statements

## composer scripts
commands are mapped to composer scripts:
- `composer shopware-create-migration`
- `composer shopware-create-schema`

# Execute Migrations
- plugin needs to be updated or re-installed 
- or run bin/console database:migrate

# References
- https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/database-migrations.html#create-migration
- https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/database-migrations.html#sql-schema