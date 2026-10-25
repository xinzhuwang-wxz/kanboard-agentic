# Third-party notices

This integration retains Kanboard's complete upstream source, MIT `LICENSE` (Frédéric Guillot), Composer lockfile, vendor license files and bundled library notices. It does not relicense upstream components.

- Kanboard: https://github.com/kanboard/kanboard, v1.2.56, commit `e197e71af4ff9f371b931ab0338b2b41827e6394`, MIT.
- Agentic Everything: separate installation; its MIT LICENSE, NOTICE and dependency notices apply. Its Node dependencies are not copied into this PHP repository.
- Composer production dependencies listed in `composer.lock` (Christian Riesen base32/OTP, Pimple, PSR interfaces and Symfony components/polyfills) declare MIT; the full original notices remain under `vendor/*`.
- Bundled library full texts remain at `libs/erusev/parsedown/LICENSE.txt`, `libs/swiftmailer/LICENSE`, `libs/Captcha/LICENSE`, `libs/jsonrpc/LICENSE`, `libs/minify/LICENSE`, `libs/picodb/LICENSE`, `libs/event-dispatcher/LICENSE`, `libs/phpqrcode/LICENSE`, `libs/path-converter/LICENSE`, and `libs/ical/LICENSE`.
- **PHP QR Code is LGPL-3.0**; retain its full license and source. LGPLv3 incorporates GPLv3; the complete GPLv3 text is additionally supplied at `licenses/GPL-3.0.txt`, alongside the unchanged original LGPLv3 text at `libs/phpqrcode/LICENSE`. The root MIT license does not override this component's terms. Other bundled code retains its own source headers and copyright statements, including `libs/SimpleQueue` and `libs/SimpleValidator`.

PHP, Chromium, PHPUnit and Symfony Stopwatch used by the acceptance environment are verification tooling, not bundled runtime binaries in this Git repository. Their own licenses remain with those installations. The qualification environment used official Debian PHP packages with signed metadata verification and upstream-pinned test tooling; no binary redistribution is implied by the source checkout.
