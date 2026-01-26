# Responsible Disclosure

We take the security of Core PHP Framework seriously. If you believe you have found a security vulnerability, we encourage you to let us know right away.

## Reporting a Vulnerability

**Email:** support@host.uk.com

**PGP Key:** Available on request

Please include the following information in your report:

- Type of vulnerability
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

## What to Expect

1. **Acknowledgment** - We will acknowledge receipt of your vulnerability report within 24 hours

2. **Investigation** - We will investigate and validate the vulnerability

3. **Response Timeline** - Based on severity:
   - **Critical**: 24-48 hours for initial response, patch within 7 days
   - **High**: 48-72 hours for initial response, patch within 14 days
   - **Medium**: 7 days for initial response, patch within 30 days
   - **Low**: 14 days for initial response, patch within 60 days

4. **Fix Development** - We will develop a fix and notify you when it's ready for testing

5. **Disclosure** - We will coordinate disclosure timing with you

## Our Commitment

- We will respond to your report promptly
- We will keep you informed of our progress
- We will credit you in our security advisory (unless you prefer to remain anonymous)
- We will not take legal action against you for responsible disclosure

## What We Ask

- Give us reasonable time to respond before disclosing the vulnerability publicly
- Make a good faith effort to avoid privacy violations, data destruction, and service interruption
- Don't access or modify data that doesn't belong to you
- Don't perform actions that could negatively affect our users

## Out of Scope

The following are **out of scope**:

- Clickjacking on pages with no sensitive actions
- Unauthenticated/logout CSRF
- Attacks requiring physical access to a user's device
- Social engineering attacks
- Attacks involving physical access to servers
- Denial of Service attacks
- Spam or social engineering techniques
- Reports from automated tools or scanners without validation

## Severity Classification

### Critical

- Remote code execution
- SQL injection
- Authentication bypass
- Privilege escalation to admin
- Exposure of sensitive data (credentials, keys)

### High

- Cross-site scripting (XSS) on sensitive pages
- Cross-site request forgery (CSRF) on sensitive actions
- Server-side request forgery (SSRF)
- Insecure direct object references to sensitive data
- Path traversal
- XML external entity (XXE) attacks

### Medium

- XSS on non-sensitive pages
- Missing security headers
- Information disclosure (non-sensitive)
- Open redirects

### Low

- Missing rate limiting on non-critical endpoints
- Verbose error messages
- Best practice violations without direct security impact

## Recognition

We maintain a Hall of Fame for security researchers who have responsibly disclosed vulnerabilities:

**2026**
- TBD

If you would like to be listed, please let us know in your disclosure email.

## Legal

This disclosure policy is based on industry best practices. By participating in our responsible disclosure program, you agree to:

- Comply with all applicable laws
- Not access or modify data beyond what is necessary to demonstrate the vulnerability
- Not perform actions that degrade our services
- Keep vulnerability details confidential until we have released a fix

We commit to not pursuing legal action against researchers who:

- Follow this policy
- Act in good faith
- Don't violate any other laws or agreements

## Example Report

```
Subject: [SECURITY] SQL Injection in PostController

Vulnerability Type: SQL Injection
Severity: High
Affected Component: Mod/Blog/Controllers/PostController.php

Description:
The search functionality in PostController does not properly sanitize
user input before constructing SQL queries, allowing SQL injection.

Steps to Reproduce:
1. Navigate to /blog/search
2. Enter payload: ' OR '1'='1
3. Observe database data exposure

Impact:
Attacker can read arbitrary data from the database, including user
credentials and API keys.

Proof of Concept:
[Include curl command or video demonstration]

Suggested Fix:
Use parameterized queries or Eloquent ORM instead of raw SQL.

Contact:
[Your name/handle]
[Your email]
[Your PGP key if applicable]
```

## Updates to This Policy

We may update this policy from time to time. The latest version will always be available at:

https://docs.core-php.dev/security/responsible-disclosure

## Contact

For security issues: support@host.uk.com

For general inquiries: https://github.com/host-uk/core-php/issues

## References

- [OWASP Vulnerability Disclosure Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Vulnerability_Disclosure_Cheat_Sheet.html)
- [ISO/IEC 29147:2018](https://www.iso.org/standard/72311.html) - Vulnerability disclosure
- [ISO/IEC 30111:2019](https://www.iso.org/standard/69725.html) - Vulnerability handling processes
