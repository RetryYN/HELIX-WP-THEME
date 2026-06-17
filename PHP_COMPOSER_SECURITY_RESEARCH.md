# PHP Composer Security Scanning & SBOM Research

**Research Date**: 2026-06-15
**Scope**: Composer dependency audit tools, vulnerability scanning, SBOM generation, CI/CD pipeline integration
**Status**: Complete research with actionable recommendations

---

## Executive Summary

For AGENT-NEO WordPress FSE theme (PHP-based), the recommended security stack is:

1. **Native `composer audit`** (2024+, built-in to Composer) — Zero config dependency scanning
2. **CycloneDX PHP Composer** (v5.1+) — SBOM generation in XML/JSON formats (Spec 1.4)
3. **Trivy** (v0.52+) — Universal vulnerability scanning for containers + filesystem
4. **GitHub Actions** — Native CI/CD orchestration with SARIF reporting

This stack provides:
- ✅ Automatic vulnerability detection (40,000+ packages covered)
- ✅ SBOM compliance (CycloneDX 1.4 / SPDX compatible)
- ✅ Policy enforcement (block PRs on critical vulns)
- ✅ Automated reporting (Slack/email alerts)
- ✅ Zero external SaaS dependencies (except optional Snyk)

---

## 1. Composer Audit & Dependency Security Tools

### Official Composer Audit (RECOMMENDED)

**Tool**: Built-in to Composer 2.5+ (PHP 8.1+)
**Status**: Official, actively maintained by Composer team (2024)
**Cost**: Free, zero external dependencies

**How it works**:
```bash
composer audit
composer audit --format=json
composer audit --locked  # Check only locked versions
```

**Features**:
- Checks against Composer Advisory Database (real-time)
- Supports 40,000+ packages
- Output formats: table (human), JSON (CI/CD)
- Severity levels: critical, high, medium, low
- No configuration required
- Works offline after initial fetch

**Command Reference**:
```bash
# Install dependencies with audit
composer install --no-dev

# Run audit (fails if vulns found)
composer audit

# JSON output for CI/CD
composer audit --format=json > audit-report.json

# Check only locked versions (faster)
composer audit --locked

# Fail on specific severity（.advisories の実形状: {"vendor/package":[{severity,...},...]}）
# ADR-021 §composer audit 判定と文言を揃えた正規コマンド
# ※ CI/CD で使う場合は set -o pipefail を先頭に付けること（前段 composer audit 失敗を伝播させるため）
composer audit --format=json | jq -e '[.advisories[][] | select(.severity=="high" or .severity=="critical")] | length == 0'
```

### Composer Advisory Database (Official Source)

**Primary Source**: https://composer.github.io/security
- **JSON Endpoint**: https://repo.packagist.org/security-advisories.json
- **Update Frequency**: Real-time
- **Coverage**: 40,000+ packages
- **Format**: JSON, CVSS scores, CVE links

**Alternative Sources**:
1. FriendsOfPHP/security-advisories (community-curated, GitHub)
2. GitHub Advisory Database (GHSA-integrated, Dependabot)
3. NIST NVD (authoritative CVE mapping)

### Advanced Alternatives (if needed)

#### Grype (Anchore)
**URL**: https://github.com/anchore/grype
**Latest**: v0.75.0+ (2024)
**Use Case**: Multi-language vulnerability scanning across entire codebase

```bash
# Install
curl -sSfL https://raw.githubusercontent.com/anchore/grype/main/install.sh | sh -s -- -b /usr/local/bin

# Scan
grype dir:. --format cyclonedx-xml > sbom.xml
grype dir:. --severity critical,high
```

#### Trivy (Aqua Security)
**URL**: https://github.com/aquasecurity/trivy
**Latest**: v0.52.0+ (2024)
**Use Case**: Container + filesystem scanning, SBOM generation

```bash
# Install
curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh -s -- -b /usr/local/bin

# Scan filesystem
trivy fs --format sarif --output trivy-results.sarif .

# Scan Docker image
trivy image php:8.3-fpm

# Generate SBOM
trivy image --format cyclonedx-json php:8.3-fpm > sbom.json
```

#### Snyk (SaaS + CLI)
**URL**: https://snyk.io
**Model**: Freemium SaaS with CLI
**Cost**: Free tier up to 200K SLOCs; Enterprise plans available
**Best for**: Managed vulnerability intelligence + remediation guidance

```bash
# Install
npm install -g snyk

# Test project
snyk test --severity-threshold=high

# Generate SBOM
snyk sbom --format=cyclonedx > sbom.json
```

---

## 2. SBOM Generation for PHP/Composer

### CycloneDX PHP Composer (RECOMMENDED)

**Project**: https://github.com/CycloneDX/cyclonedx-php-composer
**Latest**: v5.1+ (2024)
**Status**: Official CycloneDX implementation for PHP
**Spec Support**: CycloneDX 1.0, 1.1, 1.2, 1.3, 1.4

#### Installation

**Option A: Development dependency**
```bash
composer require --dev cyclonedx/cyclonedx-php-composer:^5.1
```

**Option B: Global / Standalone**
```bash
composer global require cyclonedx/cyclonedx-php-composer:^5.1
which cyclonedx-php-composer
```

#### Usage

**Generate XML SBOM (Spec 1.4)**:
```bash
vendor/bin/cyclonedx-php-composer make-sbom \
  --spec-version 1.4 \
  --output-format xml \
  --output-file sbom.xml
```

**Generate JSON SBOM**:
```bash
vendor/bin/cyclonedx-php-composer make-sbom \
  --spec-version 1.4 \
  --output-format json \
  --output-file sbom.json
```

**Full Options**:
```bash
vendor/bin/cyclonedx-php-composer help make-sbom

# Output:
# --spec-version    Version of CycloneDX spec (1.0|1.1|1.2|1.3|1.4) [default: 1.4]
# --output-file     Output file path
# --output-format   Output format (xml|json) [default: xml]
# --exclude-dev     Exclude dev dependencies
# --version         Show version
# --help            Show help
```

#### Integration into composer.json

```json
{
  "require": {
    "php": ">=8.1"
  },
  "require-dev": {
    "cyclonedx/cyclonedx-php-composer": "^5.1"
  },
  "scripts": {
    "sbom": "cyclonedx-php-composer make-sbom --spec-version 1.4 --output-format xml --output-file sbom.xml",
    "sbom:json": "cyclonedx-php-composer make-sbom --spec-version 1.4 --output-format json --output-file sbom.json",
    "sbom:all": [
      "@sbom",
      "@sbom:json"
    ]
  }
}
```

#### SBOM Validation

CycloneDX provides official validators:

```bash
# Option 1: Online validator (https://cyclonedx.org/validator)

# Option 2: Java validator (standalone)
curl -L https://github.com/CycloneDX/cyclonedx-core-java/releases/download/v7.3.0/cyclonedx-core-7.3.0-all.jar \
  -o cyclonedx-validator.jar

java -jar cyclonedx-validator.jar \
  --input-file sbom.xml \
  --input-format xml \
  --schema-version 1.4
```

### Alternative SBOM Formats

#### SPDX (Software Package Data Exchange)
**URL**: https://spdx.dev
**Advantages**: More standardized, government adoption (NTIA)
**Disadvantage**: Less PHP-native tooling

```bash
# Limited PHP support; CycloneDX recommended
```

#### Syft (Anchore - SBOM generation only)
**URL**: https://github.com/anchore/syft
**Use Case**: Pure SBOM generation without vulnerability scanning

```bash
curl -sSfL https://raw.githubusercontent.com/anchore/syft/main/install.sh | sh -s -- -b /usr/local/bin

syft dir:. --output cyclonedx-xml > sbom.xml
syft packages  # Show detected packages
```

---

## 3. Vulnerability Databases & Advisory Feeds

### Composer Official Advisory Database

**Primary Endpoint**: https://composer.github.io/security
**JSON Feed**: https://repo.packagist.org/security-advisories.json
**Update**: Real-time (checked on every `composer install` / `composer audit`)
**Scope**: 40,000+ PHP packages

**Example Advisory Entry**:
```json
{
  "advisories": {
    "vendor/package": [
      {
        "title": "SQL Injection in X",
        "link": "https://...",
        "cve": "CVE-2024-12345",
        "date": "2024-01-15",
        "severity": "critical",
        "affectedVersions": [">=1.0,<1.0.5"],
        "patchedVersions": ["1.0.5"],
        "sources": {
          "packagist": "https://packagist.org/advisories/..."
        }
      }
    ]
  }
}
```

### GitHub Advisory Database

**URL**: https://github.com/advisories
**Coverage**: PHP packages, GHSA IDs (GitHub Security Advisories)
**Integration**: Dependabot, CodeQL

**Accessing via API**:
```bash
# GraphQL API
curl -H "Authorization: token YOUR_TOKEN" \
  -d '{"query":"{ securityVulnerabilities(first:10,after:null) { nodes { advisory { identifier } } } }"}' \
  https://api.github.com/graphql
```

### NIST National Vulnerability Database

**URL**: https://nvd.nist.gov
**Scope**: CVE/CPE authoritative source
**PHP Integration**: CPE lookup for components

```bash
# Example CPE for PHP package
cpe:/a:symfony:http-foundation:5.4.0
```

### FriendsOfPHP/security-advisories (Community)

**URL**: https://github.com/FriendsOfPHP/security-advisories
**Status**: Actively maintained community database
**Format**: JSON + YAML
**Used by**: Legacy tools (security-checker), reference implementations

---

## 4. CI/CD Pipeline Integration

### GitHub Actions (RECOMMENDED)

#### Minimal Setup (Composer Audit Only)

```yaml
name: Security Audit

on: [push, pull_request]

jobs:
  audit:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          tools: composer
      
      - run: composer install --no-dev
      - run: composer audit
```

#### Standard Setup (Audit + SBOM + Container Scan)

```yaml
name: Security Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
  schedule:
    - cron: '0 2 * * 0'  # Weekly

jobs:
  composer-audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          tools: composer
      
      - name: Install dependencies
        run: composer install
      
      - name: composer audit gate
        run: |
          set -o pipefail
          # composer audit は脆弱性検出時に非0終了する。
          # ここでは JSON を確実にファイルへ書き、不正・空ファイルも fail 扱いにする。
          # （|| true で握り潰すと audit 失敗時に空ファイルが残り gate が無効化される）
          # .advisories の実形状: {"vendor/package":[{severity,...},...]}（パッケージ別オブジェクト of 配列）
          # ADR-021 §composer audit 判定と文言を揃えた正規コマンド
          composer audit --format=json > audit.json || true
          test -s audit.json || { echo "::error::audit.json が空です（composer audit が失敗した可能性があります）"; exit 1; }
          if ! jq -e '[.advisories[][] | select(.severity=="high" or .severity=="critical")] | length == 0' audit.json; then
            echo "::error::High/Critical vulnerabilities found"
            exit 1
          fi
      
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: composer-audit
          path: audit.json

  sbom-generation:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          tools: composer
      
      - run: composer install --no-dev
      
      - run: composer require --dev cyclonedx/cyclonedx-php-composer:^5.1
      
      - run: vendor/bin/cyclonedx-php-composer make-sbom \
              --spec-version 1.4 --output-format xml --output-file sbom.xml
      
      - run: vendor/bin/cyclonedx-php-composer make-sbom \
              --spec-version 1.4 --output-format json --output-file sbom.json
      
      - uses: actions/upload-artifact@v3
        with:
          name: sbom
          path: |
            sbom.xml
            sbom.json

  trivy-scan:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      security-events: write
    
    steps:
      - uses: actions/checkout@v4
      
      - uses: aquasecurity/trivy-action@master
        with:
          scan-type: fs
          scan-ref: .
          format: sarif
          output: trivy-results.sarif
          severity: CRITICAL,HIGH,MEDIUM
      
      - uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: trivy-results.sarif

  container-scan:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      security-events: write
    
    steps:
      - uses: actions/checkout@v4
      
      - uses: docker/build-push-action@v5
        with:
          context: .
          load: true
          tags: php-app:latest
      
      - uses: aquasecurity/trivy-action@master
        with:
          image-ref: php-app:latest
          format: sarif
          output: container-scan.sarif
      
      - uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: container-scan.sarif

  security-report:
    runs-on: ubuntu-latest
    needs: [composer-audit, sbom-generation, trivy-scan]
    if: always()
    
    steps:
      - uses: actions/download-artifact@v3
      
      - run: |
          echo "# Security Scan Summary" >> $GITHUB_STEP_SUMMARY
          echo "" >> $GITHUB_STEP_SUMMARY
          echo "## Artifacts" >> $GITHUB_STEP_SUMMARY
          echo "- Composer Audit Report" >> $GITHUB_STEP_SUMMARY
          echo "- SBOM (XML/JSON)" >> $GITHUB_STEP_SUMMARY
          echo "- Trivy Scan Results" >> $GITHUB_STEP_SUMMARY
```

#### With Snyk Integration

```yaml
name: Snyk Security

on: [push, pull_request]

jobs:
  snyk:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - uses: snyk/actions/setup@master
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          tools: composer
      
      - run: composer install
      
      - name: Run Snyk test
        env:
          SNYK_TOKEN: ${{ secrets.SNYK_TOKEN }}
        run: snyk test --severity-threshold=high --json-file-output=snyk-report.json
      
      - name: Generate SBOM
        env:
          SNYK_TOKEN: ${{ secrets.SNYK_TOKEN }}
        run: snyk sbom --format=cyclonedx > sbom.json
      
      - uses: actions/upload-artifact@v3
        with:
          name: snyk-report
          path: |
            snyk-report.json
            sbom.json
```

### GitLab CI

#### Native Dependency Scanning

```yaml
include:
  - template: Security/Dependency-Scanning.gitlab-ci.yml

variables:
  DS_EXCLUDED_PATHS: tests/,vendor/
```

#### Custom Composer Audit Job

```yaml
security:audit:
  stage: test
  image: php:8.3-cli
  
  before_script:
    - apt-get update && apt-get install -y composer
    - composer install
  
  script:
    - composer audit --format=json | tee audit.json
  
  artifacts:
    reports:
      dependency_scanning: audit.json
    paths:
      - audit.json
  
  allow_failure: true
```

#### SBOM Generation

```yaml
sbom:generate:
  stage: build
  image: php:8.3-cli
  
  script:
    - apt-get update && apt-get install -y composer
    - composer install --no-dev
    - composer require --dev cyclonedx/cyclonedx-php-composer
    - vendor/bin/cyclonedx-php-composer make-sbom \
        --spec-version 1.4 --output-format xml --output-file sbom.xml
  
  artifacts:
    paths:
      - sbom.xml
    reports:
      sbom: sbom.xml
```

---

## 5. Best Practices & Recommendations

### Composer Configuration (composer.json)

```json
{
  "require": {
    "php": ">=8.1"
  },
  "require-dev": {
    "cyclonedx/cyclonedx-php-composer": "^5.1",
    "phpstan/phpstan": "^1.10"
  },
  "scripts": {
    "audit": "composer audit",
    "audit:json": "composer audit --format=json",
    "sbom": "cyclonedx-php-composer make-sbom --spec-version 1.4 --output-format xml --output-file sbom.xml",
    "sbom:json": "cyclonedx-php-composer make-sbom --spec-version 1.4 --output-format json --output-file sbom.json",
    "security": [
      "@audit",
      "@sbom",
      "@sbom:json"
    ]
  }
}
```

### Policy Enforcement

**Block PRs on Critical Vulnerabilities**:
```yaml
- name: Fail on critical vulns
  run: |
    set -o pipefail
    # composer audit は脆弱性検出時に非0終了する。
    # set -o pipefail により、パイプ前段の失敗も exit code に伝播させる。
    # .advisories の実形状: {"vendor/package":[{severity,...},...]}（パッケージ別オブジェクト of 配列）
    # ADR-021 §composer audit 判定と文言を揃えた正規コマンド
    if ! composer audit --format=json | jq -e '[.advisories[][] | select(.severity=="high" or .severity=="critical")] | length == 0'; then
      echo "::error::Found high/critical vulnerabilities"
      exit 1
    fi
```

**Enforce SBOM in Releases**:
```yaml
- name: Verify SBOM exists
  if: github.event_name == 'release'
  run: test -f sbom.xml || exit 1
```

**Upload SBOM to Dependency-Track (OWASP)**:
```bash
curl -X POST \
  -H "X-API-Key: $DEPENDENCY_TRACK_API_KEY" \
  -H "Content-Type: application/xml" \
  -d @sbom.xml \
  https://dependency-track-instance/api/v1/bom
```

### Local Development

```bash
# Run security checks locally before commit
composer security

# Or individually:
composer audit
composer sbom
composer sbom:json
```

### GitHub Security Tab Integration

Results automatically appear in:
- **Security** → **Code scanning alerts** (from SARIF uploads)
- **Security** → **Dependabot alerts** (from GitHub Actions)
- **Security** → **Dependency graph** (for package visualization)

---

## 6. Comparison Matrix

| Tool | Type | Latest | PHP Support | SBOM | Output | Cost | Status |
|------|------|--------|-------------|------|--------|------|--------|
| **composer audit** | Built-in | 2024+ | Native | No | JSON/Table | Free | ✅ Recommended |
| **CycloneDX PHP** | SBOM Gen | v5.1+ | Native | Yes | XML/JSON | Free | ✅ Recommended |
| **Trivy** | Scanner | v0.52+ | Yes | Yes | SARIF/JSON | Free/OSS | ✅ Recommended |
| **Grype** | Scanner | v0.75+ | Yes | Yes | CycloneDX | Free/OSS | ✅ Good Alternative |
| **Snyk** | SaaS + CLI | Current | Yes | Yes | JSON/CycloneDX | Freemium | ✅ Enterprise Option |
| security-checker | Legacy | 2022 (EOL) | Yes | No | JSON | Free | ❌ Deprecated |

---

## 7. Recommended Implementation for AGENT-NEO

### Phase 1: Core (Week 1)

**Setup composer audit + basic CI/CD**:
```bash
# Local development
composer audit
composer install

# Add to GitHub Actions (minimal)
# See: GitHub Actions section above
```

### Phase 2: SBOM (Week 2)

**Add CycloneDX SBOM generation**:
```bash
composer require --dev cyclonedx/cyclonedx-php-composer:^5.1
composer sbom
```

**Update CI/CD** to generate and upload SBOM on release.

### Phase 3: Advanced (Week 3+)

**Add Trivy filesystem scan** for comprehensive coverage.
**Add Snyk** if enterprise SaaS integration desired.
**Setup OWASP Dependency-Track** for centralized SBOM management.

---

## References & URLs

### Official Documentation
- Composer Audit: https://getcomposer.org/doc/03-cli.md#audit
- Composer Security: https://composer.github.io/security
- CycloneDX PHP: https://github.com/CycloneDX/cyclonedx-php-composer
- CycloneDX Spec: https://cyclonedx.org/

### Tools
- Trivy: https://github.com/aquasecurity/trivy
- Grype: https://github.com/anchore/grype
- Snyk: https://snyk.io
- Syft: https://github.com/anchore/syft

### Vulnerability Databases
- Composer Advisory DB: https://repo.packagist.org/security-advisories.json
- GitHub Advisory DB: https://github.com/advisories
- NIST NVD: https://nvd.nist.gov
- FriendsOfPHP: https://github.com/FriendsOfPHP/security-advisories

### CI/CD Integrations
- GitHub Actions: https://github.com/marketplace?type=actions&query=security
- GitLab CI Templates: https://docs.gitlab.com/ee/ci/examples/
- OWASP Dependency-Track: https://owasp.org/www-project-dependency-track/

---

## Document Info

- **Research Date**: 2026-06-15
- **Last Updated**: 2026-06-15
- **Scope**: AGENT-NEO WordPress FSE theme (PHP/Composer)
- **Status**: Final - Ready for implementation
