# Author Change by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/author-change.svg)](https://packagist.org/packages/fof/author-change) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

A [Flarum](http://flarum.org) extension. Let mods update the author and date of discussions and posts.

The author edit button is added underneath the title/tag edit button for discussions and under the content edit button for posts.

### Author edit

Users with "Update author" permission must also have the "View user list" permission to be able to search for users.

### Date edit

The field uses the native [datetime-local](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local) picker in browsers that support it.
The field format in supported browsers will match your browser/operating system language and country setting.

**The time in the modal is UTC.**

### Other

The first post of a discussion and the discussion itself are not automatically synced.
You will probably want to edit the data in both places, or use the sync option in the modal.

### Installation

Install with Composer:

```sh
composer require fof/author-change:"*"
```

### Updating

```sh
composer update fof/author-change:"*"
```

### Links

[![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)

- [Packagist](https://packagist.org/packages/fof/author-change)
- [GitHub](https://github.com/FriendsOfFlarum/author-change)
- [Discuss](https://discuss.flarum.org/d/39503)

This extension was originally created by [Clark Winkelmann](https://clarkwinkelmann.com/) and is now maintained by [FriendsOfFlarum](https://github.com/FriendsOfFlarum).
