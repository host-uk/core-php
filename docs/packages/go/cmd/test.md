# core test

Run Go tests with coverage reporting and clean output.

## Usage

```bash
core test [flags]
```

## Flags

| Flag | Description |
|------|-------------|
| `--verbose` | Stream test output as it runs |
| `--coverage` | Show detailed per-package coverage breakdown |
| `--pkg <pattern>` | Package pattern to test (default: `./...`) |
| `--run <regex>` | Run only tests matching this regex |
| `--short` | Skip long-running tests |
| `--race` | Enable race detector |
| `--json` | Output JSON for CI/agents |

## Examples

```bash
# Run all tests with coverage summary
core test

# Show test output as it runs
core test --verbose

# Show detailed coverage by package
core test --coverage

# Test specific packages
core test --pkg ./pkg/crypt
core test --pkg ./pkg/...

# Run specific tests by name
core test --run TestHash
core test --run "Test.*Good"

# Skip integration tests
core test --short

# Check for race conditions
core test --race

# CI/agent mode with JSON output
core test --json
```

## Output

### Default Output

```
Test: Running tests
  Package: ./...

  ✓ 14 passed

  Coverage: 75.1%

PASS All tests passed
```

### With `--coverage` Flag

```
Test: Running tests
  Package: ./...

  ✓ 14 passed

  Coverage by package:
    pkg/crypt              91.2%
    pkg/crypt/lthn         100.0%
    pkg/io                 96.0%
    pkg/plugin             93.3%
    pkg/runtime            83.3%
    pkg/workspace          73.9%
    pkg/container          65.6%
    pkg/release            40.8%
    pkg/php                26.0%
    pkg/release/publishers 13.3%

    Average                75.1%

PASS All tests passed
```

### JSON Output (`--json`)

```json
{
  "passed": 14,
  "failed": 0,
  "skipped": 0,
  "coverage": 75.1,
  "exit_code": 0,
  "failed_packages": []
}
```

## Features

### macOS Linker Warning Suppression

Sets `MACOSX_DEPLOYMENT_TARGET=26.0` automatically to suppress CGO linker warnings on macOS. The warnings are also filtered from output for clean DX.

### Coverage Colour Coding

Coverage percentages are colour-coded:
- **Green**: 80%+ coverage
- **Amber**: 50-79% coverage
- **Red**: Below 50% coverage

### Package Name Shortening

Package names are shortened for readability:
- `github.com/host-uk/core/pkg/crypt` → `pkg/crypt`

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All tests passed |
| 1 | One or more tests failed |

## See Also

- [build command](build.md) - Build Go projects
- [doctor command](doctor.md) - Check development environment
