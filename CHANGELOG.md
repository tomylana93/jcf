# Changelog

## [1.1.3](https://github.com/tomylana93/jcf/compare/v1.1.2...v1.1.3) (2026-11-06)


### Bug Fixes

* **system:** report health check probe failures ([#82](https://github.com/tomylana93/jcf/issues/82)) ([4f74214](https://github.com/tomylana93/jcf/commit/4f742144d2f701012fc5d29a32fdc81b512e97c5))

## [1.1.2](https://github.com/tomylana93/jcf/compare/v1.1.1...v1.1.2) (2026-11-06)


### Continuous Integration

* **actions:** cache playwright system packages ([#80](https://github.com/tomylana93/jcf/issues/80)) ([1c27708](https://github.com/tomylana93/jcf/commit/1c27708240d6625430de2286b2414598505a0212))

## [1.1.1](https://github.com/tomylana93/jcf/compare/v1.1.0...v1.1.1) (2026-11-06)


### Bug Fixes

* **system:** update nginx web image to 1.30.6 ([#78](https://github.com/tomylana93/jcf/issues/78)) ([5ded091](https://github.com/tomylana93/jcf/commit/5ded0917ac487e5759d8ebacdbf944d67579e8e5))

## [1.1.0](https://github.com/tomylana93/jcf/compare/v1.0.1...v1.1.0) (2026-11-06)


### Features

* **api:** make the external api a toggleable module ([#59](https://github.com/tomylana93/jcf/issues/59)) ([a973f5f](https://github.com/tomylana93/jcf/commit/a973f5fd1dfdd7b7cd2e71f1a76483f2b065450a))
* **api:** manage partner api clients from master data ([#60](https://github.com/tomylana93/jcf/issues/60)) ([861bf30](https://github.com/tomylana93/jcf/commit/861bf3060e910403aeedf983ce5c18485a0d3bd9))
* **api:** manage personal api tokens from account settings ([#58](https://github.com/tomylana93/jcf/issues/58)) ([3aab8dd](https://github.com/tomylana93/jcf/commit/3aab8ddd228fe564314eeb8aed2af00303eef937))
* **api:** serve versioned api on a dedicated domain ([#57](https://github.com/tomylana93/jcf/issues/57)) ([91f9f63](https://github.com/tomylana93/jcf/commit/91f9f633b1d1211d210fc79613bf671ecce72d8e))
* **auth:** issue mobile device tokens with two-factor login ([#61](https://github.com/tomylana93/jcf/issues/61)) ([5dc290c](https://github.com/tomylana93/jcf/commit/5dc290cf981e7369ecfd2d666da2a8c27b2611a0))
* **system:** prepare production container deployment ([#77](https://github.com/tomylana93/jcf/issues/77)) ([2b233de](https://github.com/tomylana93/jcf/commit/2b233def1dfa1a7ecff80284aba87bfc0b90027c))


### Bug Fixes

* **backup:** correct worker availability check ([#74](https://github.com/tomylana93/jcf/issues/74)) ([ac69e19](https://github.com/tomylana93/jcf/commit/ac69e190c120e43ef490a6edadb58355257b248c))
* **release:** close starter kit phase 6 reuse findings ([#51](https://github.com/tomylana93/jcf/issues/51)) ([2fcce68](https://github.com/tomylana93/jcf/commit/2fcce68acf5fdf57b2d19f0ca4802117b11018c7))
* resolve security audit findings ([#75](https://github.com/tomylana93/jcf/issues/75)) ([938d246](https://github.com/tomylana93/jcf/commit/938d246cb7fcfd56f1829eea00cd42cd5dda8234))


### Performance Improvements

* **ui:** defer heavy backup and system page props ([#68](https://github.com/tomylana93/jcf/issues/68)) ([3ba319a](https://github.com/tomylana93/jcf/commit/3ba319ae47788206279b05651aa24318022f2d1b))


### Code Refactoring

* align backend code patterns and shared helpers ([#65](https://github.com/tomylana93/jcf/issues/65)) ([f171a8a](https://github.com/tomylana93/jcf/commit/f171a8a590e01a8d0e06b1bf38bfc67a287ff605))
* apply architecture audit fixes (A-01–A-05) ([#63](https://github.com/tomylana93/jcf/issues/63)) ([dc7a21c](https://github.com/tomylana93/jcf/commit/dc7a21cce7128c61bb14a4c2cf965497941d65e5))
* finish module boundaries and fix rule drift (A-06–A-15, R-01–R-05) ([#64](https://github.com/tomylana93/jcf/issues/64)) ([2b1db29](https://github.com/tomylana93/jcf/commit/2b1db29dced4f20364780c0cf68ba4d2651add87))
* **lang:** apply i18n wording audit findings ([#73](https://github.com/tomylana93/jcf/issues/73)) ([8c4e6dc](https://github.com/tomylana93/jcf/commit/8c4e6dc408423f5b6dbe480ec35a9da2b39e8e8e))
* serialize page entities through json resources ([#69](https://github.com/tomylana93/jcf/issues/69)) ([8b16ec1](https://github.com/tomylana93/jcf/commit/8b16ec10b37caa76b8f72754a8bc720f0fd23e5a))
* **ui:** resolve frontend audit inconsistencies ([#66](https://github.com/tomylana93/jcf/issues/66)) ([dc45e8c](https://github.com/tomylana93/jcf/commit/dc45e8cb046b6c7d2db7c4ff93ad8060d8331601))


### Build System

* **config:** give lerd worktrees their own api domain ([#62](https://github.com/tomylana93/jcf/issues/62)) ([7d20bd6](https://github.com/tomylana93/jcf/commit/7d20bd6070a3b16c35e71544696ff892e6a162f7))
* **deps:** update Laravel and Symfony security fixes ([#56](https://github.com/tomylana93/jcf/issues/56)) ([d5ba048](https://github.com/tomylana93/jcf/commit/d5ba048611babf0ece58d5fcd34e1dbb98fdb733))


### Continuous Integration

* **actions:** isolate pull request edit events ([#76](https://github.com/tomylana93/jcf/issues/76)) ([aa8e3b7](https://github.com/tomylana93/jcf/commit/aa8e3b73552396c86ffbdab448f5868575707d2a))
* **actions:** resolve workflow audit findings ([#72](https://github.com/tomylana93/jcf/issues/72)) ([28d256f](https://github.com/tomylana93/jcf/commit/28d256f33e4a2717c5ac6255d9a8a334a71b5155))
* run tests on pushes to main ([#55](https://github.com/tomylana93/jcf/issues/55)) ([5ec0273](https://github.com/tomylana93/jcf/commit/5ec02739f61b1ab576d837bea32ae21ace78a75d))


### Tests

* **lint:** block literal copy in Vue templates (B-10) ([#67](https://github.com/tomylana93/jcf/issues/67)) ([c83c9a1](https://github.com/tomylana93/jcf/commit/c83c9a1b69e29edf24534cac2d162157bbb6bc54))


### Documentation

* add mit license file ([#54](https://github.com/tomylana93/jcf/issues/54)) ([6877409](https://github.com/tomylana93/jcf/commit/6877409358c405cd60a6d0cc00114e62ee71d6e3))
* redesign readme with branding and badges ([#53](https://github.com/tomylana93/jcf/issues/53)) ([4d31936](https://github.com/tomylana93/jcf/commit/4d319366579cf1736619c44daa934049c862c24e))


### Miscellaneous Chores

* add Trakteer and document funding links ([#71](https://github.com/tomylana93/jcf/issues/71)) ([ad554fa](https://github.com/tomylana93/jcf/commit/ad554fa179a61f76951690580e4901757c183cfe))
* configure GitHub Sponsors ([#70](https://github.com/tomylana93/jcf/issues/70)) ([21e680b](https://github.com/tomylana93/jcf/commit/21e680b409b8dddca1ce81aa8918c777e4ae58d1))

## [1.0.1](https://github.com/tomylana93/jcf/compare/v1.0.0...v1.0.1) (2026-11-02)


### Bug Fixes

* **config:** disable optional modules by default ([#49](https://github.com/tomylana93/jcf/issues/49)) ([54f98de](https://github.com/tomylana93/jcf/commit/54f98ded21444176db86b77ae49885d8e8bdebc8))

## 1.0.0 (2026-11-02)


### Features

* **config:** add controlled starter kit installation ([#41](https://github.com/tomylana93/jcf/issues/41)) ([5300a6b](https://github.com/tomylana93/jcf/commit/5300a6bc6a89307676d6fa611d530c9878251da4))
* prepare reusable starter kit distribution and releases ([#40](https://github.com/tomylana93/jcf/issues/40)) ([ef02949](https://github.com/tomylana93/jcf/commit/ef02949e6d71bfe3e40b6fd77a7baf609ab5a4cd))


### Bug Fixes

* **config:** align starter kit release baseline ([#44](https://github.com/tomylana93/jcf/issues/44)) ([e37fcb4](https://github.com/tomylana93/jcf/commit/e37fcb45b4120c17fffcd7500ac47fd817051609))


### Performance Improvements

* **chat:** reduce redundant realtime traffic ([#42](https://github.com/tomylana93/jcf/issues/42)) ([b10a777](https://github.com/tomylana93/jcf/commit/b10a7775091e82b27e1776e37721ad50e5281bd1))
* streamline CI and remove website module ([#43](https://github.com/tomylana93/jcf/issues/43)) ([86bb1e7](https://github.com/tomylana93/jcf/commit/86bb1e7fd8f711ace28fb2c0997678b1b7f17179))
