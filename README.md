# Export and Import Data

Export and import data in EspoCRM

## Usage

### Export data

```bash
php command.php export-import export
```

### Import data

```bash
php command.php export-import import
```

### Erase imported data

```bash
php command.php export-import erase
```

### Command parameters

```
php command.php export-import import --format=json --pretty-print=true --data-path="custom/Espo/Custom/Data" --export-path="build/ExportImport" --import-type=createAndUpdate --use-default-currency=true --entity-type-list="Account,Contact"
```

## Configuration

The export / import process can be configured in `custom/Espo/Custom/Resources/metadata/app/exportImport.json`

### Import Type

#### Attribute: `importType`

#### Possible values:

- `create`
- `createAndUpdate`
- `update`

#### Default: `createAndUpdate`

### User active status

Default user status for impoted users. This applies to all user except admin user with ID `1`.

#### Attribute: `userActive`

#### Possible values:
- `true`
- `false`

#### Default: `false`

### User password

User password for imported users.
If empty then generates random values. For resseting the passord use `php command.php set-password [username]`.

#### Attribute: `userPassword`

#### Possible values:
- `any string`

#### Default: `null`

## Export Import Defs

In the metadata defs can be configured some additional features. The path is `custom/Espo/Custom/Resources/metadata/exportImportDefs`.

### Export additioanl fields

If need to export additional fields that out of the standard functionality.

#### exportAdditionalFieldList

Example for Quotes: `custom/Espo/Custom/Resources/metadata/exportImportDefs/Quote.json`

```json
{
    "exportAdditionalFieldList": [
        "itemList"
    ]
}
```

## Placeholders

Edit the file: `custom/Espo/Custom/Resources/metadata/exportImportDefs/ENTITY.json`.

### Config

#### Get

```
"placeholderAction": "Config\\Get",
"placeholderData": {
    "key": "defaultCurrency",
    "default": null
}
```

### User

#### Password

```
"placeholderAction": "User\\Password",
"placeholderData": {
    "value": "1"
}
```

## Development

Mode information about configuring the extension for development purposes, read the https://github.com/espocrm/ext-template#readme.

## License

This extension is published under the GNU GPLv3 license.
