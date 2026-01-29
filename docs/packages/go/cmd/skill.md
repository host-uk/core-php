# Claude Code Skill

The `core` CLI includes a Claude Code skill that helps Claude use the correct commands when working in host-uk repositories.

## What It Does

The skill provides Claude with:
- Command quick reference for all `core` commands
- Decision tree for choosing the right command
- Common mistakes to avoid
- Best practices for testing, building, and releasing

## Installation

### Automatic (Project-Based)

When working in any host-uk repository that includes `.claude/skills/core/`, Claude automatically discovers and uses the skill.

### Global Install

Install the skill globally so it works in any project:

```bash
# If you have the repo cloned
cd /path/to/core
./.claude/skills/core/install.sh

# Or via curl
curl -fsSL https://raw.githubusercontent.com/host-uk/core/main/.claude/skills/core/install.sh | bash
```

This copies the skill to `~/.claude/skills/core/`.

## Usage

### Automatic Invocation

Claude automatically uses the skill when:
- Running tests in a Go project
- Building or releasing
- Working across multiple repos
- Checking CI status or issues

### Manual Invocation

Type `/core` in Claude Code to see the full command reference.

## What Claude Learns

### Testing

```
Wrong: go test ./...
Right: core test

Why: core test sets MACOSX_DEPLOYMENT_TARGET, filters linker warnings,
     and provides colour-coded coverage output.
```

### Building

```
Wrong: go build
Right: core build

Why: core build handles cross-compilation, code signing, archiving,
     and checksums automatically.
```

### Multi-Repo Workflows

```
Wrong: cd into each repo, run git status
Right: core health

Why: Aggregated view across all repos in one command.
```

## Command Reference

The skill includes documentation for:

| Category | Commands |
|----------|----------|
| Testing | `core test`, `core test --coverage`, `core test --json` |
| Building | `core build`, `core build --targets`, `core build --ci` |
| Releasing | `core release`, `core sdk` |
| Multi-Repo | `core health`, `core work`, `core commit`, `core push`, `core pull` |
| GitHub | `core issues`, `core reviews`, `core ci` |
| Environment | `core doctor`, `core setup`, `core search`, `core install` |
| PHP | `core php dev`, `core php artisan` |
| Containers | `core run`, `core ps`, `core stop`, `core logs`, `core exec` |
| Docs | `core docs list`, `core docs sync` |

## Customisation

The skill is a markdown file at `.claude/skills/core/SKILL.md`. You can:

1. **Fork and modify** - Copy to your own repo's `.claude/skills/` and customise
2. **Extend** - Add project-specific commands or workflows
3. **Override** - Project skills take precedence over global skills

## Troubleshooting

### Skill Not Loading

Check if the skill exists:
```bash
ls ~/.claude/skills/core/SKILL.md
# or
ls .claude/skills/core/SKILL.md
```

### Reinstall

```bash
rm -rf ~/.claude/skills/core
curl -fsSL https://raw.githubusercontent.com/host-uk/core/main/.claude/skills/core/install.sh | bash
```

## See Also

- [test command](test.md) - Run tests with coverage
- [build command](build.md) - Build projects
- [work command](work.md) - Multi-repo operations
