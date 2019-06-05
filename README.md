<div align="center" style="font-size:24px;font-weight:bold;margin-bottom:20px;">
  Akeneo Purge Job Artifacts Bundle
</div>

This bundle comes with a new command to remove leftover job execution artifacts
(files in the archive which are not linked to a database job execution anymore).

## Requirements

| Version | Akeneo PIM Community Edition | Akeneo PIM Enterprise Edition |
|:-------:|:----------------------------:|:-----------------------------:|
| 1.0.*   | 2.3.*                        | 2.3.*                         |

## Installation

```bash
    composer require eikona-media/akeneo-purge-job-artifacts:~1.0
```

3) Enable the bundle in the `app/AppKernel.php` file in the `registerProjectBundles()` method:
```php
protected function registerProjectBundles()
{
    return [
        // ...
        new EikonaMedia\Akeneo\PurgeJobArtifactsBundle\EikonaMediaAkeneoPurgeJobArtifactsBundle(),
    ];
}

```

## Usage

To remove leftover job execution artifacts execute the command `eikona-media:batch:purge-job-execution-artifacts`.  
The command has one option: `--force`. If you omit the option the command runs in safe mode (no files will be deleted).


The command searches for directories with a numeric name in the third level of the `archive` directory:

```
- archive
    - export
        - csv_product_export
            - 22 // Third level with a numeric name
            - 23 // Third level with a numeric name
            - 24 // Third level with a numeric name
    - import
        - csv_product_import
            - 25 // Third level with a numeric name
    - ...
        - ...
            - 26 // Third level with a numeric name
            - 27 // Third level with a numeric name
```

The found directories are checked against the ids in the table `akeneo_batch_job_execution`.  
All directories which do not have an entry in the table will be deleted.
 

