# Export Import tool for EspoCRM

A powerful tool for transferring data between EspoCRM instances using CLI commands. The Export Import extension enables you to manage data transfer workflows, continuous delivery pipelines, and data synchronization across your EspoCRM deployments.

## What can be transferred

- Records
- Settings
- Customizations
- Files

## Use cases

- **Continuous Delivery Pipeline**: Transfer roles, workflows, BPM flowcharts from development to production environments.
- **Demo Data Management**: Easily create and update demo instances with consistent data.
- **Instance Migration**: Seamlessly migrate to another EspoCRM instance.
- **Change Tracking**: Track and manage changes across your EspoCRM infrastructure.
- **Data Recovery**: Restore updated or deleted records from previous exports.

## Usage

### Export

```bash
bin/command export-import export
```

### Import

```bash
bin/command export-import import
```

### Compare

```bash
bin/command export-import compare --format=json --path="../data/"
```

### Erase

```bash
bin/command export-import erase --format=json --path="../data/" --user-skip-list="admin"
```

## Documentation

For more information see the [documentation](https://docs.espocrm.com/extensions/export-import/overview/).

## Development

More information about configuring the extension for development purposes, read the https://github.com/espocrm/ext-template#readme.

## License

This extension is published under GNU AGPLv3 [license](https://raw.githubusercontent.com/espocrm/ext-export-import/master/LICENSE).
