# HEPTAconnect

> Repository template for production integration

This repository is intended to be forked and modified to your individual use case.
It provides a quickstart for projects aiming for a standalone application based on HEPTAconnect.
[You can learn more about HEPTAconnect in the documentation.](https://heptaconnect.io/guides/integrator/)

## Installation

1. Fork this repository. Clone your fork to your local machine and navigate to the repository on your command line interface.
2. Run `composer install`.
3. Run `bin/console system:setup` to generate your `.env` file. The wizard will prompt you for database credentials.
4. Run `bin/console system:install`. If your database does not exist yet, add `--create-database` to the command.
5. Run `bin/console system:update:finish`.
6. Configure the document root directory (`/public`) to be hosted by a webserver under a dedicated hostname.
    1. If you are using macOS, we recommend [Laravel Valet](https://laravel.com/docs/9.x/valet).
7. Run `bin/console heptaconnect:config:base-url:set <your-hostname>` where you replace `<your-hostname>` with the previously configured hostname.

✅ That's it. The system installation is complete.

## Development

* You can install additional portals or portal extensions via composer.
    * Run `composer require niemand-online/heptaconnect-portal-amiibo`.
    * Run `bin/console heptaconnect:portal-node:add 'NiemandOnline\HeptaConnect\Portal\Amiibo\AmiiboPortal' amiibo`
* You can develop custom portals or portal extensions by adding them in the directory `/src/Portal`.
    * Create a new directory `/src/Portal/HelloWorld`.
    * Inside this new directory create a class `HeptaConnect\Production\Portal\HelloWorld\HelloWorldPortal` that extends `Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract`.
    * Run `bin/console cache:clear`.
    * Run `bin/console heptaconnect:portal-node:add 'HeptaConnect\Production\Portal\HelloWorld\HelloWorldPortal' hello-world`.
    * [Read more about portal development in the documentation.](https://heptaconnect.io/guides/portal-developer/)
* You can create migrations to get reproducible database operations that run once per installation.
    * Run `bin/console database:create-migration` to generate a new migration file in `/src/Integration/Migration`.
    * The `\HeptaConnect\Production\Integration\Component\Migration\MigrationHelper` class provides convenience methods like `addPortalNode`, `addRoute` and `activatePortalExtension`.
    * You can use `\Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface` via `$migrationHelper->getStorageFacade()`. This will grant you access to every storage action of the management storage.
    * You can use `\Doctrine\DBAL\Connection` via `$migrationHelper->getConnection()`. This will grant you direct access to the underlying database.
    * ▶️ Run `bin/console system:update:finish` to apply all new migrations.

## Deployment

Your deployment strategy will influence the availability of your application and the amount of maintenance required during deployments.
Since any good deployment strategy is tailored to your specific requirements and circumstances, there is no universal solution.
So, instead of providing a complete deployment script, we provide a narrative of recommended steps.

* It is recommended to use some kind of CI/CD pipeline for your deployments. Some of the best known providers are:
    * [GitHub Actions](https://github.com/features/actions)
    * [GitLab CI/CD pipelines](https://docs.gitlab.com/ee/ci/pipelines/)
    * [Bitbucket Pipelines](https://bitbucket.org/product/features/pipelines)
* Run `composer install --no-dev` in your CI/CD pipeline.
    * Collect the files you want to deploy in an artifact.
* Stop all running cronjobs and message consumers on your target server(s).
    * If you are using [Supervisor](http://supervisord.org/), run `supervisorctl stop all`.
    * If you are using [Cron](https://de.wikipedia.org/wiki/Cron), run `crontab -r`.
* Copy your prepared artifact files to your target server(s).
* Also remember to delete files on your target server(s) that have been removed or renamed since your last deployment.
    * If you are using [rsync](https://rsync.samba.org/), use the option `--delete`.
    * ⚠️ Caution: Only apply deletions in the directories `/src` and `/vendor`! Other directories contain files that are custom for their environment and not part of your VCS.
* Run `bin/console cache:clear` on your target server(s) to clear the cache.
* Run `bin/console system:update:finish` on your target server(s) to apply database migrations.
* Finally, start your cronjobs and message consumers again.
