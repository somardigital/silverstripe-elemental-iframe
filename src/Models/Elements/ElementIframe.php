<?php

namespace NSWDPC\Elemental\Models\Iframe;

use Codem\Utilities\HTML5\UrlField;
use DNADesign\Elemental\Models\BaseElement;
use gorriecoe\Link\Models\Link;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Permission;
use SilverStripe\View\Requirements;
use SilverStripe\Model\ModelData;

/**
 * Iframe content block
 *
 * @author Mark Taylor
 * @author James Ellis
 * @property bool $IsLazy
 * @property bool $IsFullWidth
 * @property bool $IsDynamic
 * @property ?string $IsResponsive
 * @property ?string $Width
 * @property ?string $Height
 * @property ?string $AlternateContent
 * @property int $URLID
 * @method \gorriecoe\Link\Models\Link URL()
 */
class ElementIframe extends BaseElement implements PermissionProvider
{
    private static string $table_name = 'ElementIframe';

    private static string $icon = 'font-icon-code';

    private static string $singular_name = 'Iframe';

    private static string $plural_name = 'Iframes';

    private static array $default_allow_attributes = [
        'fullscreen'
    ];

    private static array $has_one = [
        'URL' => Link::class,
    ];

    private static array $db = [
        'IsLazy' => 'Boolean',
        'IsFullWidth' =>  'Boolean',
        'IsDynamic' =>  'Boolean',
        'IsResponsive' =>  'Varchar(8)',
        'Width' => 'Varchar(8)',
        'Height' => 'Varchar(8)',
        'AlternateContent' => 'Text'
    ];

    private static array $defaults = [
        'IsLazy' => 1,
        'IsFullWidth' => 1,
        'IsResponsive' => '16x9',
        'Width' => '100%',
        'Height' => '400',
    ];

    private static string $title = 'Iframe';

    private static string $class_description = 'Display content in an HTML iframe tag';

    private static array $responsive_options = [
        '16x9' => '16x9',
        '4x3' => '4x3'
    ];

    private static string $default_height = '400';

    private static bool $load_polyfill = false;

    private static bool $resizer_log = false;

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getType()
    {
        return _t(self::class . '.BlockType', 'Iframe');
    }

    /**
     * Apply requirements when templating
     */
    #[\Override]
    public function forTemplate($holder = true): string
    {

        // Responsive CSS
        if ($this->IsResponsive) {
            Requirements::css(
                'nswdpc/silverstripe-elemental-iframe:client/static/style/iframe.css',
                'screen',
                [
                    'integrity' => 'BCvA93KSwNd2uyy/627Fmtp2cpR8qUvOA2b1zO52ashQ6RPM7BoEieDfManGxC2aq9XiL2jYmwWEcRZF+3Vovw==',
                    'crossorigin' => 'anonymous'
                ]
            );
        }

        // Dynamic iframe height
        if ($this->IsDynamic) {
            Requirements::javascript(
                'https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/4.3.2/iframeResizer.min.js',
                [
                    'integrity' => 'sha512-dnvR4Aebv5bAtJxDunq3eE8puKAJrY9GBJYl9GC6lTOEC76s1dbDfJFcL9GyzpaDW4vlI/UjR8sKbc1j6Ynx6w==',
                    'crossorigin' => 'anonymous'
                ]
            );
            // Custom script, with uniquenessId to set only once in the case of > 1 iframe elements in the page
            Requirements::customScript(
                $this->DynamicCustomScript(),
                'iframe-resizer-trigger'
            );

        }

        // Lazy load polyfill, if configured and LazyLoad is on
        if ($this->IsLazy && $this->config()->get('load_polyfill')) {
            Requirements::javascript(
                "https://cdnjs.cloudflare.com/ajax/libs/loading-attribute-polyfill/1.5.4/loading-attribute-polyfill.min.js",
                [
                    "integrity" => "sha512-Kq3/MTxphzXJIRDWtrpLhhNnLDPiBXPMKkx/KogMYZO92Geor9j8sJguZ1OozBS+YVmVKo2HEx2gZfGOQBFM8A==",
                    "crossorigin" => "anonymous"
                ]
            );
        }

        return parent::forTemplate($holder);
    }

    /**
     * Return the script used to handle dynamic height changes
     * this fires iFrameResize on window.load
     */
    public function DynamicCustomScript(): string
    {
        $log = $this->config()->get('resizer_log') ? 'true' : 'false';
        return <<<JAVASCRIPT
window.addEventListener('load', function() {
    try { iFrameResize( { log: {$log} }, '.iframe-resizer iframe' ); } catch (e) { console.warn(e); }
});
JAVASCRIPT;
    }

    /**
     * Return id attribute for iframe element
     */
    public function IframeID(): string
    {
        return $this->getAnchor() . "-frame";
    }

    /**
     * Return default 'allow' attribute values
     * @return string (escaped)
     */
    public function DefaultAllowAttributes(): string
    {
        $allow = $this->config()->get('default_allow_attributes');
        if (is_array($allow) && $allow !== []) {
            $allow = array_unique($allow);
            $allow_value = htmlentities(implode(" ", $allow));
        } else {
            $allow_value = "";
        }

        return $allow_value;
    }

    /**
     * Provide a set of permissions to lock down who can add/view/delete iframe elements
     * @return array
     */
    public function providePermissions()
    {
        return [
            'ELEMENT_IFRAME_EDIT' => [
                'name' => 'Edit iframe elements',
                'category' => 'Iframe',
            ],
            'ELEMENT_IFRAME_DELETE' => [
                'name' => 'Delete iframe elements',
                'category' => 'Iframe',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'ELEMENT_IFRAME_EDIT');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'ELEMENT_IFRAME_DELETE');
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, 'ELEMENT_IFRAME_EDIT');
    }

    /**
     * @inheritdoc
     */
    public function getResponsiveOptions()
    {
        return $this->config()->get('responsive_options') ?: [];
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->Width <= 0 || $this->IsFullWidth || $this->IsResponsive) {
            $this->Width = "100%";
        }

        if ($this->Height <= 0) {
            $this->Height = $this->getDefaultHeight();
        }

        /**
         * Translate the URL value provided into a Link model URL
         * and allow to be assigned to record
         */
        $urlId = $this->saveURLtoLink($this->URLValue);
        if (!is_null($urlId)) {
            $this->URLID = $urlId;
        }
    }

    /**
     * Given a string URL, save it to the current Link model as an external URL value
     * @param string $urlValue a URL
     * @return int|null the Link model record ID or null if not a value
     */
    public function saveURLtoLink(string $urlValue = null): ?int
    {

        if (in_array($urlValue, [null, '', '0'], true)) {
            // avoid saving a link model that has no URL
            return null;
        }

        // Assign title for link record
        $title = _t(
            self::class . ".LINK_TITLE",
            "Link for iframe element {title}",
            [
                'title' => $this->exists() ? "(#{$this->ID})" : "(new)"
            ]
        );

        // Find or create a new Link record
        $link = $this->URL();
        if (!$link || !$link->exists()) {
            $link = Link::create();
        }

        // Save values as external URL type
        $link->Type = "URL";
        $link->Title = $title;
        $link->Email = "";
        $link->Phone = "";
        $link->FileID = 0;
        $link->SiteTreeID = 0;
        $link->Anchor = null;
        $link->OpenInNewWindow = 0;
        $link->SelectedStyle = "";
        $link->URL = $urlValue;

        $id = $link->write();
        return is_int($id) ? $id : null;
    }

    /**
     * Return the height, the configured height or a default height, to ensure one
     */
    public function getIframeHeight()
    {
        $height = $this->getField('Height');
        if (!$height) {
            $height = $this->getDefaultHeight();
        }

        return $height;
    }

    /**
     * Return the width or 100% if not set
     */
    public function getIframeWidth(): string
    {
        $width = $this->getField('Width');
        if (!$width || $this->IsFullWidth || $this->IsResponsive) {
            $width = "100%";
        }

        return $width;
    }

    /**
     * Return the default height or a set height of 400 if not set
     */
    protected function getDefaultHeight(): string
    {
        $height = $this->config()->get('default_height');
        if (!$height) {
            $height = '400';
        }

        return $height;
    }

    /**
     * Return the URL as a string value from the Link model
     * This provides some compatibility between previous versions that used the LinkFields to add an iframe src
     */
    public function getURLAsString(): string
    {
        $url = "";
        $link = $this->URL();
        if ($link && $link->exists()) {
            $linkURL = $link->getLinkURL();
            if (is_string($linkURL)) {
                $url = $linkURL;
            }
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'URLID'
        ]);

        $urlField = UrlField::create(
            'URLValue',
            _t(self::class. '.URL', 'The URL to use as the iframe source'),
            $this->getURLAsString()
        )->setDescription(
            _t(self::class. '.URL_DESCRIPTION', 'Pages loaded over https will require a https:// URL'),
        );

        $fields->addFieldsToTab(
            'Root.Main',
            [
                CheckboxField::create(
                    'IsLazy',
                    _t(self::class. '.LAZY_LOAD', 'Lazy load')
                )->setDescription(
                    _t(
                        self::class . '.LAZYLOAD_DESCRIPTION',
                        'When checked, load the iframe URL when contents are in view'
                    )
                ),
                CheckboxField::create(
                    'IsDynamic',
                    _t(self::class. '.DYNAMIC', 'Automatically set height to iframe content')
                )->setDescription(
                    _t(
                        self::class . '.DYNAMIC_DESCRIPTION',
                        'Requires <code>https://github.com/davidjbradshaw/iframe-resizer</code> to be installed on the remote page. This option is not compatible with responsive iframes.'
                    )
                ),
                CheckboxField::create(
                    'IsFullWidth',
                    _t(self::class. '.FULL_WIDTH', 'Enforce full width')
                )->setDescription(
                    _t(
                        self::class . '.FULL_WIDTH_DESCRIPTION',
                        'When checked, this option will override the width to 100% of the container and maintain aspect ratio'
                    )
                ),
                DropdownField::create(
                    'IsResponsive',
                    'Responsive options',
                    $this->getResponsiveOptions()
                )->setEmptyString('-not responsive-')
                    ->setDescription(
                        _t(
                            self::class . '.RESPONSIVE_DESCRIPTION',
                            'When set, this option will override the width to 100% of the container and maintain aspect ratio'
                        )
                    ),
                TextField::create(
                    'Width',
                    _t(self::class. '.WIDTH', 'Width')
                )->setDescription(
                    _t(
                        self::class . '.WIDTH_DESCRIPTION',
                        "A width value, for example <code>100%</code> or <code>800</code>. Do not specify 'px'."
                    )
                ),
                TextField::create(
                    'Height',
                    _t(self::class. '.HEIGHT', 'Height')
                )->setDescription(
                    _t(
                        self::class . '.HEIGHT_DESCRIPTION',
                        "A height value, for example <code>200</code> or <code>800</code>. Do not specify 'px'."
                    )
                ),
                TextareaField::create(
                    'AlternateContent',
                    _t(self::class. '.ALTERNATE_CONTENT', 'Alternate content for used for assistive technologies')
                )->setRows(4)
                ->setDescription(
                    _t(
                        self::class. '.ALTERNATE_CONTENT_DESCRIPTION',
                        "This value should concisely describe the embedded content to people using assistive technologies such as a screen reader"
                    )
                )
            ]
        );

        $fields->insertBefore('IsLazy', $urlField);
        return $fields;
    }

    /**
     * Return whether lazy load polyfill is enabled, useful in templates
     */
    public function HasPolyfill(): bool
    {
        return (bool)self::config()->get('load_polyfill');
    }

}
