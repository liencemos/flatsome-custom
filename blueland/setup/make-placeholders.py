#!/usr/bin/env python3
"""Generate neutral product and lifestyle placeholders for the Pureland demo."""

from pathlib import Path
import math
import random
import sys

from PIL import Image, ImageDraw, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "child-theme" / "blueland-child" / "assets" / "images"
OUTPUT.mkdir(parents=True, exist_ok=True)
random.seed(17)

PALETTES = [
    ("#b9d8f5", "#e5f2ff"),
    ("#f4d8c9", "#fff1e8"),
    ("#d8e7cf", "#f2f8ec"),
    ("#d9d5ed", "#f2effa"),
    ("#f4e6ad", "#fff8dc"),
    ("#c5e6df", "#effaf7"),
    ("#e9ccd9", "#fff0f7"),
    ("#c7d5eb", "#f1f5fb"),
]
PRODUCTS = (
    "clean-suite-kit",
    "daily-dish-starter-kit",
    "refill-starter-set",
    "laundry-starter-set",
    "dishwashing-tablet-refills",
    "laundry-detergent-refills",
    "hand-soap-starter-duo",
    "hand-soap-refills",
    "toilet-bowl-starter-kit",
    "multi-surface-spray-set",
    "reusable-spray-bottle",
    "scrub-sponge-set",
    "clean-home-essentials-kit",
    "laundry-essentials-kit",
)


def font(size):
    for name in ("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"):
        try:
            return ImageFont.truetype(name, size)
        except OSError:
            pass
    return ImageFont.load_default()


def gradient(size, top, bottom):
    width, height = size
    image = Image.new("RGB", size)
    draw = ImageDraw.Draw(image)
    a = tuple(bytes.fromhex(top.lstrip("#")))
    b = tuple(bytes.fromhex(bottom.lstrip("#")))
    for y in range(height):
        ratio = y / max(1, height - 1)
        color = tuple(round(a[i] * (1 - ratio) + b[i] * ratio) for i in range(3))
        draw.line((0, y, width, y), fill=color)
    return image


def product_image(path, index, name):
    image = Image.new("RGBA", (1100, 1100), "#f2f2f2")
    shadow = Image.new("RGBA", image.size, (0, 0, 0, 0))
    ImageDraw.Draw(shadow).ellipse((225, 790, 885, 925), fill=(18, 23, 45, 46))
    image = Image.alpha_composite(image, shadow.filter(ImageFilter.GaussianBlur(26)))
    products = Image.new("RGBA", image.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(products)
    dark, light = PALETTES[index % len(PALETTES)]
    label = name.replace("-", " ").upper()
    kraft = "#c8ac87"
    is_kit = any(word in label for word in ("KIT", "SET", "DUO"))

    def pouch(x, y, width, height, accent):
        draw.rounded_rectangle((x, y, x + width, y + height), radius=28, fill="#ad8f6b")
        draw.rounded_rectangle((x + 9, y + 8, x + width - 9, y + height - 8), radius=24, fill=kraft)
        draw.line((x + 22, y + 34, x + width - 22, y + 34), fill="#9c7c5b", width=5)
        draw.ellipse((x + width * .31, y + height * .34, x + width * .69, y + height * .66), fill=accent)
        draw.ellipse((x + width * .40, y + height * .43, x + width * .60, y + height * .57), outline="#fffdf7", width=5)

    def bottle(x, y, width, height, accent):
        body_top = y + int(height * .25)
        draw.rounded_rectangle((x, body_top, x + width, y + height), radius=int(width * .2), fill=light)
        draw.rounded_rectangle((x + 10, body_top + 12, x + width - 10, y + height - 10), radius=int(width * .17), outline="#ffffff", width=5)
        draw.rectangle((x + width * .37, y + height * .14, x + width * .63, body_top + 5), fill="#f8f7f2")
        draw.rounded_rectangle((x + width * .32, y + height * .06, x + width * .68, y + height * .20), radius=10, fill="#f8f7f2")
        draw.rounded_rectangle((x + width * .18, y + height * .47, x + width * .82, y + height * .72), radius=18, fill=accent)
        draw.ellipse((x + width * .42, y + height * .52, x + width * .58, y + height * .68), outline="#fffdf7", width=5)

    def tin(x, y, width, height, accent):
        draw.rounded_rectangle((x, y + 25, x + width, y + height), radius=42, fill=light)
        draw.rounded_rectangle((x, y + 5, x + width, y + 75), radius=24, fill="#e7e7e1")
        draw.line((x + 10, y + 75, x + width - 10, y + 75), fill="#b8b8b2", width=4)
        draw.ellipse((x + width * .32, y + height * .40, x + width * .68, y + height * .76), fill=accent)
        draw.ellipse((x + width * .42, y + height * .50, x + width * .58, y + height * .66), outline="#fffdf7", width=5)

    if is_kit:
        pouch(175, 305, 245, 495, dark)
        pouch(650, 350, 250, 440, PALETTES[(index + 3) % len(PALETTES)][0])
        bottle(405, 215, 300, 610, PALETTES[(index + 5) % len(PALETTES)][0])
    elif "SPONGE" in label:
        for item in range(3):
            x = 225 + item * 220
            draw.rounded_rectangle((x, 390, x + 175, 700), radius=55, fill=PALETTES[(index + item) % len(PALETTES)][0], outline="#fff", width=8)
            draw.rounded_rectangle((x, 570, x + 175, 700), radius=42, fill="#c7b29d")
    elif "BOTTLE" in label or "SPRAY" in label or "SOAP" in label:
        bottle(340, 150, 420, 730, dark)
    elif "LAUNDRY" in label:
        tin(300, 270, 500, 575, dark)
        pouch(660, 405, 240, 410, PALETTES[(index + 2) % len(PALETTES)][0])
    elif "REFILL" in label or "TABLET" in label:
        pouch(300, 235, 500, 620, dark)
    else:
        tin(300, 270, 500, 575, dark)
    image = Image.alpha_composite(image, products)
    image.convert("RGB").save(path, quality=94)


def make_gallery_variants(path, slug):
    base = Image.open(path).convert("RGB")
    for index, angle in enumerate((-5, -2, 2, 5), start=2):
        scale = 1.0 + (index - 3) * .035
        size = int(1100 / scale)
        left = (1100 - size) // 2
        view = base.crop((left, left, left + size, left + size)).resize((1100, 1100), Image.Resampling.LANCZOS)
        view = view.rotate(angle, resample=Image.Resampling.BICUBIC, fillcolor="#f2f2f2")
        view.save(path.with_name(f"{slug}-{index}.webp"), "WEBP", quality=82, method=6)


def lifestyle(path, palette, title, size=(1600, 1050)):
    image = gradient(size, palette[0], palette[1]).convert("RGBA")
    width, height = size
    overlay = Image.new("RGBA", size, (0, 0, 0, 0))
    d = ImageDraw.Draw(overlay)
    d.ellipse((-width * .12, height * .42, width * .58, height * 1.3), fill=(255, 255, 255, 84))
    d.ellipse((width * .60, -height * .35, width * 1.12, height * .65), fill=(255, 255, 255, 70))
    # Abstract sunlit botanical forms.
    d.polygon([(width * .1, height), (width * .32, height * .34), (width * .46, height),], fill=(184, 203, 165, 135))
    d.polygon([(width * .16, height), (width * .39, height * .48), (width * .58, height)], fill=(209, 219, 185, 155))
    # Product silhouettes.
    d.rounded_rectangle((width * .68, height * .30, width * .83, height * .86), radius=40, fill=(244, 249, 250, 230), outline=(255, 255, 255, 255), width=8)
    d.rounded_rectangle((width * .72, height * .19, width * .79, height * .36), radius=12, fill=(245, 246, 239, 245))
    d.rounded_rectangle((width * .71, height * .53, width * .80, height * .70), radius=7, fill=(255, 255, 255, 240))
    d.text((width * .715, height * .58), "PURE", font=font(max(18, width // 75)), fill="#133cd1")
    d.text((width * .71, height * .63), "CLEAN", font=font(max(14, width // 100)), fill="#444a7b")
    # A soft, sun-washed texture without external photography.
    overlay = overlay.filter(ImageFilter.GaussianBlur(1.5))
    image = Image.alpha_composite(image, overlay).convert("RGB")
    image.save(path, quality=92)


def make_badge(path, label, index):
    image = Image.new("RGBA", (320, 320), "#eff5ff")
    draw = ImageDraw.Draw(image)
    draw.ellipse((24, 24, 296, 296), outline="#133cd1", width=8)
    draw.ellipse((42, 42, 278, 278), outline="#9da3bd", width=3)
    draw.text((160, 112), "✦" if index % 2 == 0 else "✓", font=font(56), fill="#133cd1", anchor="mm")
    words = label.split()
    y = 190
    for word in words[:2]:
        draw.text((160, y), word.upper(), font=font(21), fill="#133cd1", anchor="mm")
        y += 28
    image.save(path)


def make_icon(path, index):
    paths = (
        '<circle cx="50" cy="50" r="23"/><path d="M50 12v12M50 76v12M12 50h12M76 50h12M24 24l9 9M67 67l9 9M76 24l-9 9M33 67l-9 9"/><path d="m38 53 8 8 17-21"/>',
        '<path d="M50 10c19 0 34 15 34 34 0 20-15 36-34 46C31 80 16 64 16 44 16 25 31 10 50 10Z"/><path d="M37 40h26M37 52h26M42 29c5 4 11 4 16 0"/>',
        '<path d="M50 12 87 80H13L50 12Z"/><path d="M50 38v19M50 68v2"/>',
        '<path d="M22 32h56v52H22z"/><path d="M34 32V20h32v12M33 51h34M43 62h14"/>',
    )
    svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none" stroke="#133cd1" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">' + paths[index % len(paths)] + '</svg>'
    path.write_text(svg, encoding="utf-8")


def make_wordmark(path):
    image = Image.new("RGBA", (640, 130), (255, 255, 255, 0))
    draw = ImageDraw.Draw(image)
    face = font(76)
    draw.text((12, 20), "PURELAND", font=face, fill="#133cd1")
    text_width = draw.textbbox((12, 20), "PURELAND", font=face)[2]
    x = text_width + 12
    box = (x, 40, x + 58, 98)
    draw.pieslice(box, 90, 270, fill="#133cd1")
    draw.arc(box, 0, 360, fill="#133cd1", width=3)
    image.save(path)


def make_press_logo(path, label, style):
    styles = (
        'font-family="Georgia,serif" font-size="31" font-style="italic" font-weight="700"',
        'font-family="Impact,sans-serif" font-size="32" font-weight="700" letter-spacing="3"',
        'font-family="Trebuchet MS,sans-serif" font-size="30" font-weight="700" letter-spacing="-1"',
        'font-family="cursive" font-size="33" font-style="italic" font-weight="600"',
    )
    svg = f'<svg xmlns="http://www.w3.org/2000/svg" width="360" height="80" viewBox="0 0 360 80"><text x="180" y="51" text-anchor="middle" fill="#5e6493" {styles[style % len(styles)]}>{label}</text></svg>'
    path.write_text(svg, encoding="utf-8")


def main():
    for index, slug in enumerate(PRODUCTS):
        product_path = OUTPUT / f"{slug}.png"
        product_image(product_path, index, slug)
        if slug in {"clean-suite-kit", "daily-dish-starter-kit", "refill-starter-set", "laundry-starter-set", "hand-soap-starter-duo", "toilet-bowl-starter-kit", "clean-home-essentials-kit", "laundry-essentials-kit"}:
            make_gallery_variants(product_path, slug)
    aliases = {
        "kit-foam": "hand-soap-starter-duo",
        "kit-multi": "clean-suite-kit",
        "kit-tablet": "dishwashing-tablet-refills",
        "kit-spray": "multi-surface-spray-set",
    }
    for alias, slug in aliases.items():
        Image.open(OUTPUT / f"{slug}.png").save(OUTPUT / f"{alias}.png")
    for index, title in enumerate(("Refill Ready", "Thoughtful Design", "Clear Information", "Reusable Format", "Everyday Use", "Concentrated Formula", "Low-Waste Choice", "Made for Home"), 1):
        make_badge(OUTPUT / f"cert-{index}.png", title, index)
    for index in range(1, 5):
        make_icon(OUTPUT / f"usp-{index}.svg", index - 1)
    make_wordmark(OUTPUT / "pureland-wordmark.png")
    for index, label in enumerate(("The Daily Edit", "HOME/GOOD", "Green Review", "MORNING"), 1):
        make_press_logo(OUTPUT / f"press-{index}.svg", label, index - 1)
    print(f"Generated placeholder assets in {OUTPUT}")


if __name__ == "__main__":
    main()
