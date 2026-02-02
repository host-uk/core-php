# Dependency and Supply Chain Audit Report

## Summary

This report provides a security audit of the project's dependencies and supply chain. The audit covers both direct and transitive dependencies, lock file integrity, package sources, and the build process. All scans include transitive dependencies.

## PHP Dependencies (Composer)

### Vulnerability Analysis (`composer audit`)

No security vulnerability advisories were found for the current set of Composer dependencies.

### Outdated Packages (`composer outdated`)

The following packages have updates available:

| Package | Current Version | Latest Version | Description |
|---|---|---|---|
| infection/infection | 0.32.3 | 0.33.0 | Infection is a Mutation Testing framework for PHP. |
| mockery/mockery | 1.6.12 | 1.7.2 | Mockery is a simple yet flexible PHP mock object library for use in unit testing. |
| nunomaduro/collision | v8.8.3 | v8.9.1 | Collision is a beautiful error reporting tool for command-line applications. |
| phpunit/phpunit | 11.5.50 | 12.5.8 | The PHP Unit Testing framework. |
| barryvdh/laravel-ide-helper | 3.5.5 | 3.6.1 | Laravel IDE Helper, generates meta files for your IDE. |
| iamcal/sql-parser | 0.6 | 0.7 | MySQL schema parser |
| netresearch/jsonmapper | 4.5.0 | 5.0.0 | Map nested JSON structures onto your PHP classes |
| phpdocumentor/reflection-docblock | 5.6.6 | 6.0.1 | With this component, a library can provide support for annotations. |
| phpdocumentor/type-resolver | 1.12.0 | 2.0.0 | A PSR-5 based resolver of Class names, Types and Structural Elements. |
| phpunit/php-code-coverage | 11.0.12 | 12.5.2 | Library that provides collection, processing, and rendering functionality for PHP code coverage information. |
| phpunit/php-file-iterator | 5.1.0 | 6.0.0 | FilterIterator implementation that filters files based on a list of suffixes. |
| phpunit/php-invoker | 5.0.1 | 6.0.0 | Invoke callables with a timeout. |
| phpunit/php-text-template | 4.0.1 | 5.0.0 | Simple template engine. |
| phpunit/php-timer | 7.0.1 | 8.0.0 | Utility class for timing |
| sebastian/cli-parser | 3.0.2 | 4.2.0 | Library for parsing CLI options |
| sebastian/comparator | 6.3.3 | 7.1.4 | Provides the functionality to compare PHP values for equality. |
| sebastian/complexity | 4.0.1 | 5.0.0 | Library for calculating the complexity of PHP code. |
| sebastian/diff | 6.0.2 | 7.0.0 | Diff implementation |
| sebastian/environment | 7.2.1 | 8.0.3 | Provides functionality to handle HHVM/PHP environments |
| sebastian/exporter | 6.3.2 | 7.0.2 | Provides the functionality to export PHP variables for textual representation. |
| sebastian/global-state | 7.0.2 | 8.0.2 | Snapshotting of global state |
| sebastian/lines-of-code | 3.0.1 | 4.0.0 | Library for counting the lines of code in PHP source code. |
| sebastian/object-enumerator | 6.0.1 | 7.0.0 | Traverses array structures and object graphs to enumerate them. |
| sebastian/object-reflector | 4.0.1 | 5.0.0 | Allows reflection of object attributes, including inherited and non-public ones. |
| sebastian/recursion-context | 6.0.3 | 7.0.1 | Provides functionality to safely recursively traverse data structures. |
| sebastian/type | 5.1.3 | 6.0.3 | Collection of value objects that represent the types of the PHP type system. |
| sebastian/version | 5.0.2 | 6.0.0 | Library that helps with managing the version number of a project. |
| theseer/tokenizer | 1.3.1 | 2.0.1 | A small library for converting token streams into XML and vice versa. |

### Lock File (`composer.lock`)

- `composer.lock` is present in the repository, ensuring consistent dependencies across environments.

## Node.js Dependencies (npm)

### Vulnerability Analysis (`npm audit`)

`npm audit` found 0 vulnerabilities.

### Outdated Packages (`npm outdated`)

`npm outdated` found no outdated packages.

### Lock File (`package-lock.json`)

- `package-lock.json` is present in the repository, which is crucial for locking dependency versions.

## Supply Chain Risks

### Package Sources

- All Composer packages are sourced from the default Packagist registry.
- All npm packages are sourced from the default npm registry.
- No custom or private registries are configured, which reduces the risk of typosquatting and other registry-based attacks.

### Build Process

- The project uses GitHub Actions for CI/CD, with workflows defined in the `.github/workflows/` directory.
- The primary workflow, `tests.yml`, focuses on running tests and does not include security-specific steps.
- **Recommendation:** Integrate `composer audit` and `npm audit` into the CI/CD pipeline to automatically check for vulnerabilities on each push or pull request.
- **Recommendation:** Use a tool like Dependabot to automate dependency updates and receive notifications about security vulnerabilities.
- The build process does not include artifact signing, which could be a risk if the project distributes build artifacts.
## License Compliance

### Composer Dependencies

The project's license is `EUPL-1.2`. The `composer licenses` command was run to analyze the licenses of all direct and transitive dependencies. The most common licenses are:

- **MIT:** A permissive license.
- **BSD-3-Clause:** A permissive license.
- **Apache-2.0:** A permissive license.

All identified dependency licenses are compatible with the project's `EUPL-1.2` license.

### NPM Dependencies

The `npx license-checker --summary` command was used to analyze the licenses of all npm `devDependencies`. The vast majority of packages are licensed under the MIT license, with others using similarly permissive licenses such as ISC, Apache-2.0, and BSD. All identified licenses are appropriate for development dependencies and do not pose any compliance issues.
