from io import BytesIO
from pathlib import Path
from random import Random

import cairosvg
from PIL import Image

OUT = Path(__file__).resolve().parents[1] / "child-theme/solana-merch-child/assets/art"
INK, PRUSSIAN, NAVY, BUS, GOLD = "#000814", "#001D3D", "#003566", "#FFC300", "#FFD60A"
PAPER, FOG, MIST, GRAPHITE = "#FAFAFA", "#EAEAEA", "#F3F3F3", "#262626"
TEES = ("carrier-wave", "night-grid", "orbital-index", "relay-station", "static-field", "after-hours")
HATS = ("receiver-cap", "sideband-cap", "orbit-cap", "uplink-cap", "field-cap", "nightshift-cap")

TEE = "M179 147 256 105 Q320 134 384 105 L461 147 557 226 501 333 442 301 449 527 Q320 548 191 527 L198 301 139 333 83 226Z"
CAP = "M128 349 Q113 302 127 256 Q155 151 271 141 Q365 129 409 218 Q426 253 420 307 Q372 378 226 385 Q151 386 128 349Z"
BRIM = "M226 385 Q305 347 420 307 Q498 329 549 372 Q580 400 536 417 Q487 442 423 437 Q302 438 226 385Z"
MARKS = (
    '<path d="M-68 15h17l14-31 16 64 20-86 16 86 16-64 13 31h23" />',
    '<path d="M-46-46h92v92h-92zM0-46v92M-46 0h92m-92-46 92 92m0-92-92 92"/>',
    '<circle r="34"/><ellipse rx="67" ry="18" transform="rotate(-28)"/><circle cx="43" cy="-30" r="5" fill="currentColor"/>',
    '<path d="M-48 42 0-44l48 86zm22-30h52M0-44v86"/><circle cy="-44" r="7" fill="currentColor"/>',
    '<path d="M-68-35q34-35 68 0t68 0M-68-10q34-35 68 0t68 0M-68 15q34-35 68 0t68 0M-68 40q34-35 68 0t68 0"/>',
    '<path d="M0-54V54M-54 0H54m-92-38 76 76m-76 0 76-76"/><circle r="39"/><circle r="11" fill="currentColor"/>',
)


def label(x, y, value, size=12, color=GOLD, extra=""):
    return f'<text x="{x}" y="{y}" font-family="DejaVu Sans Mono" font-size="{size}" fill="{color}" {extra}>{value}</text>'


def mark(index, x, y, scale, color, word):
    return (
        f'<g transform="translate({x} {y}) scale({scale})" color="{color}">'
        f'<g fill="none" stroke="{color}" stroke-width="3" stroke-linecap="square">{MARKS[index]}</g>'
        + label(0, 72, word, 10, color, 'text-anchor="middle" letter-spacing="3"')
        + "</g>"
    )


def garment(hat, index, colorway):
    colors = (INK, NAVY, PRUSSIAN) if colorway == "ink" else (PRUSSIAN, NAVY, INK)
    shape = CAP if hat else TEE
    seam = GOLD if colorway == "ink" else NAVY
    defs = f"""
      <linearGradient id="fabric" x2=".88" y2="1">
        <stop stop-color="{colors[1]}"/><stop offset=".41" stop-color="{colors[0]}"/>
        <stop offset=".71" stop-color="{colors[2]}"/><stop offset="1" stop-color="{colors[0]}"/>
      </linearGradient>
      <linearGradient id="brim" x1="0" y1="0" x2="1" y2=".75">
        <stop stop-color="{colors[2]}"/><stop offset=".6" stop-color="{colors[0]}"/><stop offset="1" stop-color="{colors[1]}"/>
      </linearGradient>
      <pattern id="weave" width="5" height="7" patternUnits="userSpaceOnUse">
        <path d="M0 0h2v1H0zm3 3h2v1H3zm-2 3h1v1H1z" fill="{GOLD}" opacity=".16"/>
      </pattern>
    """
    if hat:
        details = f"""
          <path d="{BRIM}" transform="translate(2 8)" fill="{INK}"/>
          <path d="{BRIM}" fill="url(#brim)" stroke="{NAVY}" stroke-width="2"/>
          <path d="{BRIM}" fill="url(#weave)" opacity=".45"/>
          <path d="M231 385Q308 300 296 147M138 342Q165 237 260 151M230 386Q382 363 414 312M285 402Q417 417 529 382M319 416Q434 437 546 392" fill="none" stroke="{seam}" stroke-opacity=".34"/>
          <path d="M269 144q18-15 32 0" fill="{NAVY}" stroke="{GOLD}" stroke-opacity=".3"/>
          <circle cx="215" cy="214" r="3" fill="{INK}"/><circle cx="347" cy="205" r="3" fill="{INK}"/>
        """
        art = mark(index, 280, 275, .7, GOLD, "SIGNAL / SUPPLY")
    else:
        details = f"""
          <path d="M256 105Q320 134 384 105Q376 166 320 168Q262 166 256 105" fill="{INK}" stroke="{NAVY}" stroke-width="3"/>
          <path d="M268 110Q281 150 320 151Q360 150 373 110M179 147Q197 192 198 301M461 147Q443 192 442 301M89 230l51 94 55-30m252 0 55 30 48-92M192 516Q320 535 448 516" fill="none" stroke="{seam}" stroke-opacity=".32" stroke-width="1.5"/>
          <path d="M209 319Q197 411 213 503M421 319Q440 407 428 500M211 191l31-58m157 0 30 58" fill="none" stroke="{NAVY}" stroke-width="5" opacity=".44"/>
          <path d="M222 337q-9 68-5 128m204-128q9 68 5 128" fill="none" stroke="{GOLD}" opacity=".08"/>
          <rect x="312" y="126" width="15" height="8" fill="{BUS}" opacity=".75"/>
        """
        art = mark(index, 320, 286, 1.14, GOLD, "SIGNAL / SUPPLY")
    return f"""<defs>{defs}</defs>
      <path d="{shape}" transform="translate(10 13)" fill="{INK}" opacity=".7"/>
      <path d="{shape}" fill="url(#fabric)" stroke="{NAVY}" stroke-width="3"/>
      <path d="{shape}" fill="url(#weave)" opacity=".45"/>
      {details}{art}
    """


def backdrop(w, h, contrast):
    a, b = (PAPER, FOG) if contrast else (PAPER, MIST)
    return f"""
      <defs>
        <radialGradient id="back"><stop stop-color="{a}"/><stop offset="1" stop-color="{b}"/></radialGradient>
        <pattern id="grid" width="32" height="32" patternUnits="userSpaceOnUse">
          <path d="M32 0H0V32" fill="none" stroke="{GRAPHITE}" opacity=".16"/>
        </pattern>
      </defs>
      <rect width="{w}" height="{h}" fill="url(#back)"/>
      <rect width="{w}" height="{h}" fill="url(#grid)"/>
      <path d="M28 52V28h24m{w-104} 0h24v24m0 {h-104}v24h-24m-{w-104} 0H28v-24"
        fill="none" stroke="{GRAPHITE}" stroke-width="2" opacity=".62"/>
    """


def raster(name, w, h, body, grain=True, png=False):
    svg = f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}">{body}</svg>'
    photo = Image.open(BytesIO(cairosvg.svg2png(bytestring=svg.encode())))
    if grain:
        rng = Random(31)
        noise = Image.frombytes("L", photo.size, rng.randbytes(w * h)).point(lambda x: x // 28)
        photo = Image.composite(Image.new("RGB", photo.size, GRAPHITE), photo.convert("RGB"), noise)
    if png:
        photo.save(OUT / f"{name}.png", optimize=True)
    else:
        photo.save(OUT / f"{name}.webp", quality=84, method=6)


def studio(name, hat, index, colorway, *, wide=False, contrast=False, detail=False):
    w = 800
    h = 450 if wide else 760
    scale = 1 if wide else 1.125
    if detail:
        at = f"translate(-{370 if hat else 558} -300) scale(3)"
    else:
        at = f"translate({(w - 640 * scale) / 2} {-101 if wide else 25 if hat else 10}) scale({scale})"
    cross = f'<path d="M{w/2-9} 40h18m-9-9v18M{w/2-9} {h-40}h18m-9-9v18" stroke="{GRAPHITE}" opacity=".55"/>'
    title = "FIELD CAPS" if wide and hat else "GRAPHIC TEES" if wide else "SIGNAL / SUPPLY"
    foot = "SIGNAL FIELD MANUAL / VOL. 01" if wide else f"FIG. {index + 1:02d} / {'DETAIL' if detail else colorway.upper()}"
    raster(name, w, h, backdrop(w, h, contrast) + cross + f'<g transform="{at}">' + garment(hat, index, colorway) + "</g>"
           + label(47, 49, title, 12, GRAPHITE, 'letter-spacing="2"') + label(47, h - 43, foot, 12, GRAPHITE, 'letter-spacing="2"'))


def main():
    OUT.mkdir(exist_ok=True)
    for hat, names in ((False, TEES), (True, HATS)):
        for index, name in enumerate(names):
            for tone in ("ink", "navy"):
                studio(f"{name}-{tone}", hat, index, tone, contrast=(index + hat) % 3 == 0)
            studio(f"{name}-detail", hat, index, "ink", detail=True, contrast=(index + hat) % 3 == 0)
    studio("collection-tees", False, 2, "navy", wide=True)
    studio("collection-hats", True, 0, "ink", wide=True, contrast=True)
    w, h = 840, 748
    raster("hero", w, h, backdrop(w, h, True) +
           f'<circle cx="424" cy="345" r="257" fill="none" stroke="{GRAPHITE}" stroke-width="1" opacity=".3"/>'
           f'<circle cx="424" cy="345" r="229" fill="none" stroke="{GRAPHITE}" stroke-width="1" opacity=".24"/>'
           '<g transform="translate(22 -10) rotate(-10 370 350) scale(1.22)">' + garment(False, 0, "ink") + "</g>"
           + label(49, 53, "SIG / 001", 15, GRAPHITE, 'letter-spacing="3"')
           + label(785, 53, "01—26", 15, GRAPHITE, 'text-anchor="end" letter-spacing="3"')
           + label(49, 691, "CARRIER WAVE / GRAPHIC TEE", 13, GRAPHITE, 'letter-spacing="2"')
           + label(788, 691, "FIG. 01", 13, GRAPHITE, 'text-anchor="end" letter-spacing="2"'))
    raster("wordmark", 422, 76,
           f'<path d="M0 0h76v76H0z" fill="{BUS}"/><path d="M12 39h12l7-17 9 34 10-31 6 14h8" fill="none" stroke="{INK}" stroke-width="4"/>'
           + label(92, 54, "SIGNAL / SUPPLY", 34, GRAPHITE, 'font-weight="bold" letter-spacing="2"'),
           grain=False, png=True)


if __name__ == "__main__":
    main()
