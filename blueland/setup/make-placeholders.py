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
    image = Image.new("RGB", (1100, 1100), "#f2f2f2")
    draw = ImageDraw.Draw(image)
    dark, light = PALETTES[index % len(PALETTES)]
    draw.ellipse((210, 760, 900, 930), fill="#e6e6e6")
    label = name.replace("-", " ").upper()
    if any(word in label for word in ("KIT", "SET", "DUO")) or index in (0, 1, 2, 3, 12, 13):
        draw.rounded_rectangle((200, 300, 900, 760), radius=36, fill="#d8d2c7")
        draw.rounded_rectangle((226, 280, 870, 730), radius=28, fill="#f9f6ee")
        # Refill packets.
        for item in range(3):
            x = 270 + item * 180
            y = 390 + (item % 2) * 12
            draw.rounded_rectangle((x, y, x + 128, y + 250), radius=32, fill=PALETTES[(index + item) % len(PALETTES)][0], outline="#ffffff", width=5)
            draw.rounded_rectangle((x + 22, y + 72, x + 106, y + 172), radius=8, fill="#fffdf8")
            draw.text((x + 27, y + 103), "PURE", font=font(19), fill="#133cd1")
            draw.text((x + 25, y + 128), "REFILL", font=font(12), fill="#133cd1")
        draw.rounded_rectangle((365, 254, 710, 322), radius=20, fill=dark)
    elif "SPONGE" in label:
        for item in range(3):
            x = 245 + item * 190
            draw.rounded_rectangle((x, 395, x + 170, 690), radius=60, fill=PALETTES[(index + item) % len(PALETTES)][0], outline="#fff", width=8)
            draw.rounded_rectangle((x, 565, x + 170, 690), radius=45, fill="#c7b29d")
    elif "BOTTLE" in label or "SPRAY" in label:
        draw.rounded_rectangle((368, 325, 730, 815), radius=78, fill=dark, outline="#fff", width=10)
        draw.rounded_rectangle((475, 215, 635, 370), radius=25, fill="#f9f9f4")
        draw.rounded_rectangle((535, 145, 695, 235), radius=18, fill="#f9f9f4")
        draw.rectangle((674, 165, 820, 198), fill="#f9f9f4")
        draw.rounded_rectangle((430, 510, 670, 650), radius=16, fill="#fffdf8")
        draw.text((472, 545), "PURELAND", font=font(23), fill="#133cd1")
        draw.text((480, 585), "CLEAN HOME", font=font(15), fill="#444a7b")
    else:
        draw.rounded_rectangle((320, 280, 780, 790), radius=70, fill="#f8f8f2", outline="#fff", width=10)
        draw.rounded_rectangle((410, 200, 690, 332), radius=28, fill=dark)
        draw.rounded_rectangle((365, 480, 735, 660), radius=14, fill=light)
        draw.text((420, 520), "PURELAND", font=font(28), fill="#133cd1")
        draw.text((427, 566), "REFILL TABLETS", font=font(15), fill="#444a7b")
    draw.text((55, 1010), "PURELAND  /  " + label[:27], font=font(18), fill="#444a7b")
    image.save(path, quality=92)


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


def main():
    for index, slug in enumerate(PRODUCTS):
        product_image(OUTPUT / f"{slug}.png", index, slug)
    aliases = {
        "kit-foam": "hand-soap-starter-duo",
        "kit-multi": "clean-suite-kit",
        "kit-tablet": "dishwashing-tablet-refills",
        "kit-spray": "multi-surface-spray-set",
    }
    for alias, slug in aliases.items():
        Image.open(OUTPUT / f"{slug}.png").save(OUTPUT / f"{alias}.png")
    lifestyle(OUTPUT / "hero.png", ("#c8d1d1", "#e4cdb2"), "hero", (1920, 1250))
    lifestyle(OUTPUT / "lifestyle.png", ("#d9e8f1", "#f5dfcf"), "lifestyle", (1600, 1100))
    lifestyle(OUTPUT / "how-it-works.png", ("#abd8ef", "#eaf4f8"), "how", (1150, 1000))
    lifestyle(OUTPUT / "autoship.png", ("#b7d8e3", "#e4d6c3"), "autoship", (1150, 1000))
    for index in range(1, 5):
        lifestyle(OUTPUT / f"instagram-{index}.png", PALETTES[index], f"social-{index}", (720, 900))
    for index, title in enumerate(("Refill Ready", "Thoughtful Design", "Clear Information", "Reusable Format", "Everyday Use", "Concentrated Formula", "Low-Waste Choice", "Made for Home"), 1):
        make_badge(OUTPUT / f"cert-{index}.png", title, index)
    for index in range(1, 5):
        make_icon(OUTPUT / f"usp-{index}.svg", index - 1)
    make_wordmark(OUTPUT / "pureland-wordmark.png")
    print(f"Generated placeholder assets in {OUTPUT}")


if __name__ == "__main__":
    main()
