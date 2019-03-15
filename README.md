SEOMate plugin for Craft CMS 3.x
===

SEO, mate! It's important. That's why SEOMate provides the tools you need to craft
all the meta tags, sitemaps and JSON-LD data you need - in one highly configurable,
open and friendly package. 

SEOMate aims to do less! All the data is pulled from native Craft fields, which means 
it leaves a super-light footprint on your Craft site. 

SEOMate also comes with a SEO preview which uses Craft's native Live Preview functionality. 

![Screenshot](resources/plugin_logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0 or later. 

## Installation

To install the plugin, either install it from the plugin store, or follow these instructions:

1. Install with composer via `composer require vaersaagod/seomate` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line via `./craft install/plugin seomate`.
3. For SEOMate to do anything, you need to [configure it](#configuring)!

---

## SEOMate Overview



---

## Using SEOMate


---

## Configuring

SEOMate can be configured by creating a file named `seomate.php` in your Craft config folder, 
and overriding settings as needed. 

### cacheEnabled [bool]
*Default: `'true'`*  
Enables/disables caching of generated meta data. The cached data will be automatically
cleared when an element is saved, but it can also be completely deleted through Craft's
clear cache tool.

### cacheDuration [int]
*Default: `3600`*  
Duration of meta cache in seconds.

### previewEnabled [bool|array]
*Default: `true`*  
Enable live SEO previews in the Control Panel for everything (true), 
nothing (false) or an array of section and/or category group handles.

### siteName [string|array|null]
*Default: `null`*  
Defines the site name to be used in meta data. Can be a plain string, or an array
with site handles as keys. Example:

```  
'siteName' => 'My site'

// or

'siteName' => [
    'default' => 'My site',
    'other' => 'Another site',
]
```

If not set, SEOMate will try to get any site name defined in Craft's general config 
for the current site. If that doesn't work, the current site's name will be used.   

### metaTemplate [string]
*Default: `''`*  
SEOMate comes with a default meta template the outputs the configured meta tags. But,
every project is different, so if you want to customize the output you can use this 
setting to provide a custom template (it needs to be in your site's template path). 

### includeSitenameInTitle [bool]
*Default: `true`*  
Enables/disabled if the site name should be displayed as part of the meta title.

### sitenameTitleProperties [array]
*Default: `['title']`*  
Defines which meta title properties the site name should be added to. By default, 
the site name is only added to the `title` meta tag.

Example that also adds it to `og:title` and `twitter:title` tags:

``` 
'sitenameTitleProperties' => ['title', 'og:title', 'twitter:title']
```

### sitenamePosition [string]
*Default: `'after'`*  
Defines if the site name should be placed `before` or `after` the rest of the
meta content.

### sitenameSeparator [string]
*Default: `'|'`*  
The separator between the meta tag content and the site name.

### outputAlternate [bool]
*Default: `true`*  
Enables/disables output of alternate URLs. Alternate URLs are meant to provide
search engines with alternate URLs _for localized versions of the current page's content_. 

If you have a normal multi-locale website, you'll probably want to leave this setting
enabled. If you're running a multi-site website, where the sites are distinct, you'll
probably want to disable this. 

### altTextFieldHandle [string|null]
*Default: `null`*  
If you have a field for alternate text on your assets, you should set this 
to your field's handle. This will pull and output the text for the `og:image:alt`
and `twitter:image:alt` properties.

### defaultProfile [string|null]
*Default: `''`*  
Sets the default meta data profile to use (see the `fieldProfiles` config setting).

### fieldProfiles [array]
*Default: `[]`*  
Field profiles defines waterfalls for which fields should be used to fill which
meta tags. You can have as many or as few profiles as you want. You can define a default 
profile using the `defaultProfile` setting, and you can map your sections and category 
groups using the `profileMap` setting. You can also override which profile to use, directly 
from your templates.

Example:

```
'defaultProfile' => 'default',

'fieldProfiles' => [
    'default' => [
        'title' => ['seoTitle', 'heading', 'title'],
        'description' => ['seoDescription', 'summary'],
        'image' => ['seoImage', 'mainImage']
    ],
    'products' => [
        'title' => ['seoTitle', 'heading', 'title'],
        'description' => ['seoDescription', 'productDescription', 'summary'],
        'image' => ['seoImage', 'mainImage', 'heroMedia:media.image']
    ],
    'landingPages' => [
        'title' => ['seoTitle', 'heading', 'title'],
        'description' => ['seoDescription'],
        'image' => ['seoImage', 'heroArea:video.image', 'heroArea:singleImage.image', 'heroArea:twoImages.images', 'heroArea:slideshow.images']
    ],
],
```  

Field waterfalls are parsed from left to right. Empty or missing fields are ignored, 
and SEOMate continues to look for a valid value in the next field.

### profileMap [array]
*Default: `[]`*  
The profile map provides a way to map sections and category groups to profiles
defined in `fieldProfiles`. If a section or category group is not found in this
map, the profile defined in `defaultProfile` will be used.

```
'profileMap' => [
    'products' => 'products',
    'frontpage' => 'landingPages',
    'campaigns' => 'landingPages',
],
```

### defaultMeta [array]
*Default: `[]`*  
This setting defines the default meta data that will be used if no valid meta data
was found for the current element (ie, non of the fields provided in the field profile
existed or had valid values). 

The waterfall uses the current _context_ to search for meta data. In the example
below, we're falling back to using fields in two globals with handle `globalSeo` 
and `settings`:

```
'defaultMeta' => [
    'title' => ['globalSeo.seoTitle'],
    'description' => ['globalSeo.seoDescription', 'settings.companyInfo'],
    'image' => ['globalSeo.seoImages']
],
```

### additionalMeta [array]
*Default: `[]`*  

The additional meta setting defines all other meta data that you want SEOMate
to output. This is a convenient way to add more global meta data, that is used
throughout the site. Please note that you don't have to use this, you could also
just add the meta data directly to your meta, or html head, template.

The key defines the meta data property to output, and the value could be either
a plain text, some twig that will be parsed based on the current context, an array
which will result in multiple tags of this property being output, or a function.

In the example below, some properties are plain text (`og:type` and `twitter:card`),
some contains twig (for instance `fb:profile_id`), and for `og:see_also` we provide
a function that returns an array. 

```
'additionalMeta' => [
    'og:type' => 'website',
    'twitter:card' => 'summary_large_image',
    
    'fb:profile_id' => '{{ settings.facebookProfileId }}',
    'twitter:site' => '@{{ settings.twitterHandle }}',
    'twitter:author' => '@{{ settings.twitterHandle }}',
    'twitter:creator' => '@{{ settings.twitterHandle }}',
    
    'og:see_also' => function ($context) {
        $someLinks = [];
        $matrixBlocks = $context['globalSeo']->someLinks->all() ?? null;
        
        if ($matrixBlocks && count($matrixBlocks) > 0) {
            foreach ($matrixBlocks as $matrixBlock) {
                $someLinks[] = $matrixBlock->someLinkUrl ?? '';
            }
        }
        
        return $someLinks;
    },
],
```

### metaPropertyTypes [array]
*Default: (see below)*  
This setting defines the type and limitations of the different meta tags. Currently,
there are two valid types, `text` and `image`. 

Example/default value:
```
[
    'title,og:title,twitter:title' => [
        'type' => 'text',
        'minLength' => 10,
        'maxLength' => 60
    ],
    'description,og:description,twitter:description' => [
        'type' => 'text',
        'minLength' => 50,
        'maxLength' => 300
    ],
    'image,og:image,twitter:image' => [
        'type' => 'image'
    ],
]
```

### applyRestrictions [bool]
*Default: `false`*  
Enables/disables enforcing of restrictions defined in `metaPropertyTypes`.

### validImageExtensions [array]
*Default: `['jpg', 'jpeg', 'gif', 'png']`*  
Valid filename extensions for image property types. 

### truncateSuffix [string]
*Default: `'…'`*  
Suffix to add to truncated meta values.

### returnImageAsset [bool]
*Default: `false`*  
By default, assets will be transformed by SEOMate, and the resulting URL is
cached and passed to the template. 

By enabling this setting, the asset itself will instead be returned to the 
template. This can be useful if you want to perform more complex transforms,
or output more meta tags where you need more asset data, that can only be done
at the template level. Please note that you'll probably want to provide a custom 
`metaTemplate`, and that caching will not work (you should instead use your own 
template caching).  

### useImagerIfInstalled [bool]
*Default: `true`*  
If [Imager](https://github.com/aelvan/Imager-Craft) is installed, SEOMate will 
automatically use it for transforms (they're mates!), but you can disable this 
setting to use native Craft transforms instead. 

### imageTransformMap [array]
*Default: (see below)*  
Defines the image transforms that are to be used for the different meta image
properties. All possible options of Imager or native Craft transforms can be used. 

Default value:
```
[
    'image' => [
        'width' => 1200,
        'height' => 675,
        'format' => 'jpg',
    ],
    'og:image' => [
        'width' => 1200,
        'height' => 630,
        'format' => 'jpg',
    ],
    'twitter:image' => [
        'width' => 1200,
        'height' => 600,
        'format' => 'jpg',
    ],
]
```

Example where the Facebook and Twitter images has been sharpened, desaturated
and given a stylish blue tint (requires Imager):

``` 
'imageTransformMap' => [
    'image' => [
        'width' => 1200,
        'height' => 675,
        'format' => 'jpg'
    ],
    'og:image' => [
        'width' => 1200,
        'height' => 630,
        'format' => 'jpg',
        'effects' => [
            'sharpen' => true,
            'modulate' => [100, 0, 100], 
            'colorBlend' => ['rgb(0, 0, 255)', 0.5]
        ]
    ],
    'twitter:image' => [
        'width' => 1200,
        'height' => 600,
        'format' => 'jpg',
        'effects' => [
            'sharpen' => true,
            'modulate' => [100, 0, 100], 
            'colorBlend' => ['rgb(0, 0, 255)', 0.5]
        ]
    ],
],
```

### autofillMap [array]
*Default: (see below)*  
Map of properties that should be automatically filled by another property,
_if they're empty after the profile has been parsed_. 

Default value:
```
[
    'og:title' => 'title',
    'og:description' => 'description',
    'og:image' => 'image',
    'twitter:title' => 'title',
    'twitter:description' => 'description',
    'twitter:image' => 'image',
]
```

### tagTemplateMap [array]
*Default: (see below)*  
Map of output templates for the meta properties. 

Default value:
```
[
    'default' => '<meta name="{{ key }}" content="{{ value }}">',
    'title' => '<title>{{ value }}</title>',
    '/^og:/,/^fb:/' => '<meta property="{{ key }}" content="{{ value }}">',
]
```

### sitemapEnabled [bool]
*Default: `false`*  
Enables/disables sitemaps.

### sitemapName [string]
*Default: `'sitemap'`*  
Name of sitemap. By default it will be called `sitemap.xml`.

### sitemapLimit [int]
*Default: `500`*  
Number of URLs per sitemap. SEOMate will automatically make a sitemap index
and split up your sitemap into chunks with a maximum number of URLs as per
this setting. A lower number could ease the load on your server when the 
sitemap is being generated.

### sitemapConfig [array]
*Default: `[]`*  
Defines the content of the sitemaps. The configuration consists of two main 
keys, `elements` and `custom`. In `elements`, you can define sitemaps that 
will automatically query for elements in certain sections or based on custom 
criterias, and in `custom` you add paths that are added to a separate custom 
sitemap.

In the example below, we get all elements from the sections with handles 
`projects` and `news`, query for entries in four specific 
sections and all categories in group `newsCategories`. In addition to these¨, 
we add two custom urls.    

```
'sitemapConfig' => [
    'elements' => [
        'projects' => ['changefreq' => 'weekly', 'priority' => 0.5],
        'news' => ['changefreq' => 'weekly', 'priority' => 0.5],
        
        'indexpages' => [
            'elementType' => \craft\elements\Entry::class,
            'criteria' => ['section' => ['frontpage', 'newsListPage', 'membersListPage', 'aboutPage']],
            'params' => ['changefreq' => 'daily', 'priority' => 0.5],
        ],
        'newscategories' => [
            'elementType' => \craft\elements\Category::class,
            'criteria' => ['group' => 'newsCategories'],
            'params' => ['changefreq' => 'weekly', 'priority' => 0.2],
        ],
    ],
    'custom' => [
        '/custom-1' => ['changefreq' => 'weekly', 'priority' => 1],
        '/custom-2' => ['changefreq' => 'weekly', 'priority' => 1],
    ]
],
```

Using the expanded criteria syntax, you can query for whichever type of element, 
as long as they are registered as a valid element type in Craft.

The main sitemap index will be available on the root of your site, and named
according to the `sitemapName` config setting (`sitemap.xml` by default). The actual
sitemaps will be named using the pattern `sitemap_<elementKey>_<page>.xml` for 
elements and `sitemap_custom.xml` for the custom urls.



Example configureation file:

```
<?php

return [
    '*' => [
        'siteName' => [
            'default' => 'Default site',
            'engelsk' => 'Alternative site',
        ],
            
        'includeSitenameInTitle' => true,
        'cacheEnabled' => false,
        'cacheDuration' => 3600,
        'sitenamePosition' => 'after',
        'sitenameSeparator' => '|',
        'defaultProfile' => 'default',
        'truncateLength' => true,
        'altTextFieldHandle' => 'altText',
        
        'defaultMeta' => [
            'title' => ['globalSeo.seoTitle'],
            'description' => ['globalSeo.seoDescription'],
            'image' => ['globalSeo.seoImages']
        ],

        'fieldProfiles' => [
            'default' => [
                'title' => ['seoTitle', 'title'],
                'description' => ['seoDescription', 'summary', 'listText'],
                'image' => ['seoImages', 'mainImage']
            ],
            'portfolio' => [
                'title' => ['seoTitle','title'],
                'description' => ['seoDescription', 'summary'],
                'image' => ['seoImages', 'images', 'testMatrix:images.images'],
            ],
        ],
        
        'profileMap' => [
            'portfolio' => 'portfolio',
        ],
        
        'additionalMeta' => [
            'og:type' => 'website',
            'twitter:card' => 'summary_large_image',
            'og:see_also' => ['{{ globalSeo.seoTitle ?? "" }}', '{{ globalSeo.seoTitle ?? "" }}']
            /*
            'fb:profile_id' => '{{ settings.facebookProfileId }}',
            'twitter:site' => '@{{ settings.twitterHandle }}',
            'twitter:author' => '@{{ settings.twitterHandle }}',
            'twitter:creator' => '@{{ settings.twitterHandle }}',
            */
        ],
        
        'sitemapEnabled' => true,
        'sitemapLimit' => 10,
        'sitemapConfig' => [
            'elements' => [
                'test' => [
                    'elementType' => \craft\elements\Entry::class,
                    'criteria' => ['section' => ['lorem', 'testing']],
                    'params' => ['changefreq' => 'daily', 'priority' => 0.5],
                ],
                'loremcategories' => [
                    'elementType' => \craft\elements\Category::class,
                    'criteria' => ['group' => 'loremCategories'],
                    'params' => ['changefreq' => 'weekly', 'priority' => 0.2],
                ],
                'portfolio' => ['changefreq' => 'weekly', 'priority' => 0.5],
                'testing' => ['changefreq' => 'weekly', 'priority' => 0.5],
            ],
            'custom' => [
                '/' => ['changefreq' => 'weekly', 'priority' => 1],
                '/custom-1' => ['changefreq' => 'weekly', 'priority' => 1],
                '/custom/2' => ['changefreq' => 'weekly', 'priority' => 1],
            ]
        ],
        
    ]
];
```


---

## Twig filters

### xxx
Adds the override param and value to an URL. 
*You should always add this when linking between your sites, for instance in a site switcher*.



---

## Price, license and support

The plugin is released under the MIT license, meaning you can do what ever you want with it as long 
as you don't blame us. **It's free**, which means there is absolutely no support included, but you 
might get it anyway. Just post an issue here on github if you have one, and we'll see what we can do. 

## Changelog

See [CHANGELOG.MD](https://raw.githubusercontent.com/vaersaagod/seomate/master/CHANGELOG.md).

## Credits

Brought to you by [Værsågod](https://www.vaersaagod.no)

Icon designed by [Freepik from Flaticon](https://www.flaticon.com/authors/freepik).
