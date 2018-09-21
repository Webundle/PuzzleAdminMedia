# Puzzle Admin Media Bundle
**=========================**

Puzzle bundle for managing admin 

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

`composer require webundle/puzzle-admin-media`

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
{
    $bundles = array(
    // ...

    new Puzzle\Admin\MediaBundle\PuzzleAdminMediaBundle(),
                    );

 // ...
}

 // ...
}
```

### Step 3: Register the Routes

Load the bundle's routing definition in the application (usually in the `app/config/routing.yml` file):

# app/config/routing.yml
```yaml
puzzle_admin:
        resource: "@PuzzleAdminMediaBundle/Resources/config/routing.yml"
```

### Step 4: Configure Dependency Injection

Then, enable management bundle via admin modules interface by adding it to the list of registered bundles in the `app/config/config.yml` file of your project under:

```yaml
# Puzzle Client Media
puzzle_admin_media:
    title: media.title
    description: media.description
    icon: media.icon
    roles:
        media:
            label: 'ROLE_MEDIA'
            description: media.role.default
```

### Step 5: Configure navigation menu

Then, enable management bundle via admin modules interface by adding it to the list of registered bundles in the `app/config/config.yml` file of your project under:

```yaml
# Client Admin
puzzle_admin:
    ...
    navigation:
   		nodes:
   			media:
                label: 'media.base'
                translation_domain: 'admin'
                attr:
                    class: 'icon-media'
                parent: ~
                user_roles: ['ROLE_MEDIA', 'ROLE_ADMIN']
                tooltip: 'media.tooltip'
            media_file:
                label: 'media.file.base'
                translation_domain: 'admin'
                path: 'admin_media_file_list'
                sub_paths: ['admin_media_file_create', 'admin_media_file_update', 'admin_media_file_show']
                parent: media
                user_roles: ['ROLE_MEDIA', 'ROLE_ADMIN']
                tooltip: 'media.page.tooltip'
            media_folder:
                label: 'media.folder.base'
                translation_domain: 'admin'
                path: 'admin_media_folder_list'
                sub_paths: ['admin_media_folder_create', 'admin_media_folder_update', 'admin_media_folder_show']
                parent: media
                user_roles: ['ROLE_MEDIA', 'ROLE_ADMIN']
                tooltip: 'media.folder.tooltip'
```