# Plugin assets

This folder contains editable SVG placeholders for the WordPress.org plugin listing. WordPress.org expects PNG images for banners, icons and screenshots; use the commands below to convert the SVGs to correctly sized PNGs before uploading.

Recommended image sizes for the WordPress.org plugin directory:

- Banner (normal): `banner-772x250.png`
- Banner (high resolution): `banner-1544x500.png`
- Icon: `icon-256x256.png`, `icon-128x128.png`
- Screenshots: `assets/screenshots/screenshot-1.png`, `assets/screenshots/screenshot-2.png` (1200×900 is a good size)

Automatic conversion (ImageMagick)

If you have ImageMagick installed (the `convert` command), run the included script to generate PNGs from the SVG placeholders:

```bash
cd assets
./convert_svgs_to_pngs.sh
```

This will produce the following files alongside the SVGs:

- `banner-772x250.png`
- `banner-1544x500.png`
- `icon-256x256.png`
- `icon-128x128.png`
- `screenshots/screenshot-1.png`
- `screenshots/screenshot-2.png`

Alternative: use `rsvg-convert` (librsvg) or open the SVGs in a vector editor and export PNGs at the specified sizes.

Final steps before upload:

1. Replace the placeholder PNGs with your final artwork PNGs (exact sizes above).
2. Add the PNGs to your plugin `assets/` folder (for SVN, upload under the plugin's `assets` in the WordPress.org repository).
3. Update `readme.txt` screenshot references if you rename files.

Notes

- The SVGs here are for quick editing; WordPress.org's plugin directory requires PNGs for banners/icons. The conversion script is a convenience helper only.

