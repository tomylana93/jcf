<?php

declare(strict_types=1);

return [
    'title' => [
        'index' => 'Website',
        'content' => ':label',
        'settings' => 'Website settings',
    ],

    'card' => [
        'title' => 'Website management',
        'description' => 'Configure public pages, choose the presentation preset, and publish changes when they are ready.',
        'page' => [
            'title' => 'Pages',
            'description' => 'Structured content for each public page. Drafts stay private until published.',
        ],
        'home' => [
            'description' => 'Stable public route with hero, sections, and contact invitation.',
        ],
        'about' => [
            'description' => 'Organization story with rich text and supporting sections.',
        ],
        'contact' => [
            'description' => 'Contact details and a call-to-action for visitors.',
        ],
        'article_index' => [
            'description' => 'Article overview page with its own title, body, and styling.',
        ],
        'settings' => [
            'title' => 'Website settings',
            'description' => 'SEO defaults, navigation, footer, and contact configuration.',
        ],
        'preset' => [
            'title' => 'Presentation preset',
            'description' => 'Choose how the public website is presented. Switching presets keeps all content, URLs, and SEO fields.',
            'corporate' => 'Formal company profile: split hero, structured service columns, and a featured article grid.',
            'editorial' => 'Magazine-like storytelling: editorial hero, lead article, and ruled section dividers.',
            'minimal' => 'Quiet single-column layout with a simple hero and compact article list.',
            'current' => 'In use',
        ],
        'public_pages_disabled' => 'Public pages are currently disabled. The website stays editable and previews remain available.',
        'identity' => [
            'title' => 'Identity',
            'description' => 'The website name shown in the browser title and the public footer.',
        ],
        'seo_defaults' => [
            'title' => 'Search engine defaults',
            'description' => 'Default SEO title, description, and indexing. Each page can override them in its editor.',
        ],
        'layout' => [
            'title' => 'Navigation and footer',
            'description' => 'Header navigation links, footer text, and footer links.',
        ],
        'contact_info' => [
            'title' => 'Contact information',
            'description' => 'Shown on the contact page and in the public footer.',
        ],
    ],

    'label' => [
        'page' => 'Page',
        'published_at' => 'Published',
        'updated_at' => 'Draft saved',
        'title' => 'Title',
        'excerpt' => 'Summary',
        'body' => 'Body',
        'sections' => 'Sections',
        'visible' => 'Visible',
        'section' => [
            'hero' => [
                'description' => 'Description',
                'image_alt' => 'Hero image',
                'cta' => 'Call to action',
            ],
            'introduction' => [
                'body' => 'Text',
            ],
            'items' => [
                'title' => 'Item :number',
                'description' => 'Description',
            ],
            'articles' => [
                'featured' => 'Show featured article first',
                'limit' => 'Number of articles',
            ],
            'contact' => [
                'description' => 'Description',
                'cta' => 'Call to action',
            ],
            'type' => [
                'hero' => 'Hero',
                'introduction' => 'Introduction',
                'services' => 'Services',
                'strengths' => 'Strengths',
                'articles' => 'Articles',
                'contact' => 'Contact',
            ],
        ],
        'cta' => [
            'label' => 'Button label',
            'href' => 'Link target',
        ],
        'image' => [
            'choose' => 'Image',
            'alt' => 'Image description (alt text)',
        ],
        'cover' => 'Cover image',
        'seo' => [
            'title' => 'SEO title',
            'description' => 'SEO description',
            'robots' => 'Search engine indexing',
            'image' => 'Social sharing image',
        ],
        'identity' => 'Website name',
        'navigation' => [
            'title' => 'Navigation links',
        ],
        'navigation_link' => [
            'label' => 'Label',
            'href' => 'Link target',
        ],
        'footer' => [
            'text' => 'Footer text',
            'links' => 'Footer links',
        ],
        'contact' => [
            'email' => 'Contact email',
            'phone' => 'Phone number',
            'address' => 'Address',
            'hours' => 'Opening hours',
            'location_url' => 'Map link',
            'social_links' => 'Social profiles',
        ],
    ],

    'placeholder' => [
        'href' => 'https://… or /page',
        'robots' => 'How search engines may index the page',
    ],

    'helper' => [
        'image_types' => 'JPEG, PNG, or WebP up to 5 MB.',
        'alt_text' => 'Describe the image for people who cannot see it.',
        'publish_state' => 'Publishing stores the current saved draft. Save unsaved changes first.',
        'draft_saved' => 'Draft version :version saved.',
        'unpublished_note' => 'Editing a draft leaves the published version unchanged.',
        'preset' => 'Preview shows the website with this preset. The public site keeps its current preset until the new preset is applied.',
        'images_first_save' => 'Save the draft once to enable image uploads.',
        'no_links' => 'No links configured yet.',
        'unsaved' => 'Unsaved changes: continue without saving?',
        'unsaved_changes' => 'Unsaved changes',
        'section' => [
            'hidden' => 'Hidden sections stay in the draft and are not rendered publicly.',
        ],
    ],

    'button' => [
        'save_draft' => 'Save draft',
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
        'preview' => 'Preview',
        'draft_preview' => 'Preview draft',
        'apply_preset' => 'Apply preset',
        'add_section' => 'Add section',
        'add_item' => 'Add item',
        'add_link' => 'Add link',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'remove_section' => 'Remove section',
        'remove' => 'Remove',
        'upload_image' => 'Upload image',
        'use_image' => 'Use image',
        'view_public' => 'View public page',
    ],

    'status' => [
        'not_started' => 'Not started',
        'draft_only' => 'Draft only',
        'published' => 'Published',
        'draft_saved' => 'Draft saved.',
        'published_toast' => 'The page is public.',
        'unpublished_toast' => 'The page is no longer public.',
        'settings_updated' => 'Website settings saved.',
        'preset_applied' => 'Presentation preset applied.',
        'image_uploaded' => 'Image uploaded.',
        'preview_draft' => 'Preview of the working draft. Only signed-in managers can open this page.',
        'preview_published' => 'Preview of the published content under this presentation. The public site is unchanged.',
        'preview_empty' => 'This page is not published yet. This is how it appears once published.',
    ],

    'error' => [
        'stale_draft' => 'The draft changed. Reload before saving or publishing.',
        'slug_taken' => 'The page or slug is already reserved.',
    ],
];
