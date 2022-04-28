# Export and Import Data

Export and import data in EspoCRM

## Usage

### Export data

```bash
php command.php exportImport export
```

### Import data

```bash
php command.php exportImport import
```

### Erase imported data

```bash
php command.php exportImport erase
```

## Configuration

The export / import process can be configured in `application/Espo/Modules/ExportImport/Resources/metadata/app/exportImport.json`

## Placeholders

Edit the file: `src/files/application/Espo/Modules/ExportImport/Resources/metadata/exportImportDefs/ENTITY.json`.

### Config

#### Get

```
"placeholderAction": "Config\\Get",
"placeholderData": {
    "key": "defaultCurrency",
    "default": null
}
```

## Development

Mode information about configuring the extension for development purposes, read the https://github.com/espocrm/ext-template#readme.

## License

This extension is published under the GNU GPLv3 license.
