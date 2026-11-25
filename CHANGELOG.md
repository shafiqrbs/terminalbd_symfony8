# Changelog / পরিবর্তন তালিকা

Each entry is written in English first, then in Bangla.
প্রতিটি পরিবর্তন প্রথমে ইংরেজিতে, তারপর বাংলায় লেখা হয়েছে।

## 2026-11-25

### English

#### Fixed
- `assets:install` no longer deletes the committed files in `public/bundles`. `--no-cleanup` is now set in the Composer auto-scripts, `docker/build.sh` and `bin/build.sh`.

#### Added
- `bin/build.sh`, the local build script, and its template `bin/build.sh.dist`. It dumps the JS routes, installs assets, clears the cache and stops at the first error.
- This changelog, in English and Bangla.
- The Docker container creates the `sessions` table on start if it is missing (`docker/sql/sessions.sql`, run by `docker/entrypoint.sh`). An existing table is not changed.

#### Safety
- `bin/build.sh` does not run `doctrine:schema:update --force`, which drops every table that no entity maps to. Preview schema changes with `php bin/console doctrine:schema:update --dump-sql`, take a backup, and apply only reviewed statements.
- `bin/build.sh` does not delete `composer.lock`, so `composer install` keeps using the locked package versions.

#### Verified
- 339 of 339 entity mappings are valid.
- The service container lint passes.
- 26 tests pass.

### বাংলা

#### সংশোধন
- `assets:install` এখন আর `public/bundles`-এ কমিট করা ফাইলগুলো মুছে ফেলে না। Composer auto-scripts, `docker/build.sh` এবং `bin/build.sh`-এ `--no-cleanup` যোগ করা হয়েছে।

#### সংযোজন
- লোকাল বিল্ড স্ক্রিপ্ট `bin/build.sh` এবং এর টেমপ্লেট `bin/build.sh.dist`। এটি JS রাউট ডাম্প করে, অ্যাসেট ইনস্টল করে, ক্যাশ পরিষ্কার করে এবং প্রথম ত্রুটিতেই থেমে যায়।
- ইংরেজি ও বাংলায় এই পরিবর্তন তালিকা।
- Docker কন্টেইনার চালু হওয়ার সময় `sessions` টেবিল না থাকলে তৈরি করে (`docker/sql/sessions.sql`, চালায় `docker/entrypoint.sh`)। আগে থেকে থাকা টেবিল বদলায় না।

#### নিরাপত্তা
- `bin/build.sh` এখন `doctrine:schema:update --force` চালায় না। এই কমান্ড এমন সব টেবিল মুছে ফেলে যেগুলোর কোনো এনটিটি ম্যাপিং নেই। স্কিমা পরিবর্তনের আগে `php bin/console doctrine:schema:update --dump-sql` দিয়ে যাচাই করুন, ব্যাকআপ নিন এবং শুধু যাচাই করা স্টেটমেন্টগুলো প্রয়োগ করুন।
- `bin/build.sh` এখন `composer.lock` মুছে ফেলে না, তাই `composer install` নির্ধারিত প্যাকেজ সংস্করণই ব্যবহার করে।

#### যাচাই
- ৩৩৯টি এনটিটি ম্যাপিংয়ের সবগুলোই সঠিক।
- সার্ভিস কনটেইনার lint সফল হয়েছে।
- ২৬টি টেস্ট সফল হয়েছে।
