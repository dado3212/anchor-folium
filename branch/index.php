<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Folium Branch Study</title>
  <style>
    :root {
      --bg-top: #f8f6f1;
      --bg-bottom: #ece8dd;
      --ink: #2f2a22;
      --muted: #6f6654;
    }

    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      font-family: "Crimson Pro", "Times New Roman", serif;
      color: var(--ink);
      background:
        radial-gradient(1200px 700px at 15% 0%, rgba(255, 255, 255, 0.8), transparent 60%),
        radial-gradient(900px 550px at 100% 10%, rgba(244, 237, 219, 0.65), transparent 65%),
        linear-gradient(180deg, var(--bg-top), var(--bg-bottom));
    }

    .scene {
      position: relative;
      width: 100%;
      height: 100%;
    }

    canvas {
      width: 100%;
      height: 100%;
      display: block;
    }

    .label {
      position: fixed;
      left: 20px;
      top: 16px;
      max-width: min(430px, calc(100vw - 40px));
      font-size: clamp(15px, 1.7vw, 20px);
      line-height: 1.2;
      color: var(--muted);
      pointer-events: none;
      text-wrap: balance;
    }
  </style>
</head>
<body>
  <div class="scene">
    <canvas id="branchCanvas" aria-label="Animated branch rendering"></canvas>
    <div class="label">Procedural trunk, wrapped vines, and animated leaves.</div>
  </div>

  <script>
    (function () {
      const canvas = document.getElementById("branchCanvas");
      const ctx = canvas.getContext("2d");
      const dpr = window.devicePixelRatio || 1;

      let w = 0;
      let h = 0;
      let trunkPoints = [];
      let twigs = [];
      let leaves = [];

      const TAU = Math.PI * 2;
      const MAX_LEAF_ATTACH_DIST = 5;
      // Tweak these for branch thickness/taper without touching drawing code.
      const TRUNK_BASE_WIDTH_RATIO = 0.017; // halved width
      const TRUNK_BASE_WIDTH_MIN = 5;
      const TRUNK_TAPER_PERCENT = 72; // 72 = top is 28% of base width

      function clamp(v, lo, hi) {
        return Math.max(lo, Math.min(hi, v));
      }

      function lerp(a, b, t) {
        return a + (b - a) * t;
      }

      function rand(seed) {
        const x = Math.sin(seed * 127.1 + seed * seed * 311.7) * 43758.5453;
        return x - Math.floor(x);
      }

      function resize() {
        const rect = canvas.getBoundingClientRect();
        w = Math.max(320, Math.floor(rect.width));
        h = Math.max(320, Math.floor(rect.height));
        canvas.width = Math.floor(w * dpr);
        canvas.height = Math.floor(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        rebuild();
        paint();
      }

      function trunkWidthAt(t) {
        const base = Math.max(TRUNK_BASE_WIDTH_MIN, w * TRUNK_BASE_WIDTH_RATIO);
        const taperPct = clamp(TRUNK_TAPER_PERCENT, 0, 95) / 100;
        const top = base * (1 - taperPct);
        return base + (top - base) * t;
      }

      function rebuild() {
        trunkPoints = [];
        twigs = [];
        leaves = [];

        const segments = 70;
        const cx = w * 0.5;
        const bottom = h * 1.03;
        const top = h * 0.02;
        const height = bottom - top;

        let bend = 0;
        for (let i = 0; i <= segments; i++) {
          const t = i / segments;
          const y = bottom - t * height;
          bend += (Math.sin(i * 0.4) + Math.cos(i * 0.23)) * 0.02;
          const wind = Math.sin(t * 6.2 + 0.4) * (w * 0.035);
          const x = cx + wind + bend * w * 0.012;
          trunkPoints.push({ x, y, t });
        }

        const leafCount = Math.round(clamp(h / 5, 110, 260));
        for (let i = 0; i < leafCount; i++) {
          const seed = i * 12.73 + 17.1;
          const t = 0.03 + rand(seed) * 0.94;
          const side = rand(seed + 9.2) > 0.5 ? 1 : -1;
          const size = 4 + rand(seed + 4.1) * 15 + (1 - t) * 6;
          const widthScale = 0.32 + rand(seed + 24.3) * 0.33;
          const tipScale = 0.78 + rand(seed + 26.4) * 0.78;
          const bulge = 0.75 + rand(seed + 28.1) * 0.75;
          const foldScale = 0.64 + rand(seed + 30.9) * 0.6;
          const isCurled = rand(seed + 33.2) > 0.66;
          const curl = isCurled
            ? side * (0.14 + rand(seed + 35.6) * 0.58)
            : side * (rand(seed + 36.5) - 0.5) * 0.1;
          const gradStart = rand(seed + 61.7);
          let gradEnd = rand(seed + 67.9);
          if (Math.abs(gradEnd - gradStart) < 0.18) {
            gradEnd = clamp(gradStart + (rand(seed + 71.2) > 0.5 ? 0.22 : -0.22), 0, 1);
          }
          const gradAngle = rand(seed + 73.4) * TAU;
          const attachTwig = rand(seed + 79.8) > 0.33;
          let anchorX;
          let anchorY;
          let baseX;
          let baseY;
          let stemWidth;

          if (attachTwig) {
            const tt = clamp(t + (rand(seed + 81.2) - 0.5) * 0.08, 0.03, 0.97);
            const p = pointAt(tt);
            const width = trunkWidthAt(tt);
            const twigLen = Math.min(MAX_LEAF_ATTACH_DIST, width * (0.28 + rand(seed + 83.4) * 0.44));
            const rise = twigLen * (0.1 + rand(seed + 85.1) * 0.24);
            const sx = p.x + side * width * 0.46;
            const sy = p.y + (rand(seed + 86.7) - 0.5) * width * 0.2;
            const tx = sx + side * twigLen;
            const ty = sy - rise;

            twigs.push({
              x1: sx,
              y1: sy,
              x2: tx,
              y2: ty,
              width: Math.max(0.35, width * 0.055)
            });

            anchorX = tx;
            anchorY = ty;
            let dx = side * twigLen * (0.02 + rand(seed + 88.2) * 0.06);
            let dy = (rand(seed + 89.4) - 0.5) * width * 0.2;
            const d = Math.hypot(dx, dy) || 1;
            const limited = Math.min(MAX_LEAF_ATTACH_DIST, d);
            dx = (dx / d) * limited;
            dy = (dy / d) * limited;
            baseX = anchorX + dx;
            baseY = anchorY + dy;
            stemWidth = Math.max(0.28, width * 0.04);
          } else {
            const vt = clamp(t + (rand(seed + 91.2) - 0.5) * 0.06, 0.03, 0.97);
            const p = pointAt(vt);
            const radius = trunkWidthAt(vt) * 0.7;
            const theta = vt * 9.5 * TAU + 0.35;
            const vineSide = Math.sin(theta) >= 0 ? 1 : -1;
            const petiole = Math.min(MAX_LEAF_ATTACH_DIST, trunkWidthAt(vt) * (0.14 + rand(seed + 93.1) * 0.22));

            anchorX = p.x + Math.sin(theta) * radius;
            anchorY = p.y;
            baseX = anchorX + vineSide * petiole;
            baseY = anchorY - petiole * (0.08 + rand(seed + 94.6) * 0.2);
            stemWidth = Math.max(0.24, trunkWidthAt(vt) * 0.035);
          }

          const stemDx = baseX - anchorX;
          const stemDy = baseY - anchorY;
          const stemAngle = Math.atan2(stemDy, stemDx);
          const angle = stemAngle + side * (0.32 + rand(seed + 97.2) * 0.88);

          leaves.push({
            seed,
            anchorX,
            anchorY,
            baseX,
            baseY,
            stemWidth,
            size,
            angle,
            widthScale,
            tipScale,
            bulge,
            foldScale,
            curl,
            gradStart,
            gradEnd,
            gradAngle
          });
        }
      }

      function pointAt(t) {
        const p = clamp(t, 0, 1) * (trunkPoints.length - 1);
        const i = Math.floor(p);
        const f = p - i;
        const a = trunkPoints[i];
        const b = trunkPoints[Math.min(trunkPoints.length - 1, i + 1)];
        return {
          x: a.x + (b.x - a.x) * f,
          y: a.y + (b.y - a.y) * f
        };
      }

      function drawTwigs() {
        for (let i = 0; i < twigs.length; i++) {
          const twig = twigs[i];
          ctx.beginPath();
          ctx.moveTo(twig.x1, twig.y1);
          ctx.lineTo(twig.x2, twig.y2);
          ctx.strokeStyle = "rgb(84, 58, 35)";
          ctx.lineWidth = twig.width;
          ctx.lineCap = "round";
          ctx.stroke();

          ctx.beginPath();
          ctx.moveTo(twig.x1, twig.y1);
          ctx.lineTo(
            lerp(twig.x1, twig.x2, 0.82),
            lerp(twig.y1, twig.y2, 0.82)
          );
          ctx.strokeStyle = "rgb(126, 97, 64)";
          ctx.lineWidth = twig.width * 0.42;
          ctx.lineCap = "round";
          ctx.stroke();
        }
      }

      function drawLeaf(x, y, size, angle, shape, colorA, colorB, alpha) {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.scale(1, 0.86 * shape.foldScale);
        ctx.transform(1, 0, shape.curl * 0.35, 1, 0, 0);
        ctx.globalAlpha = alpha;
        ctx.strokeStyle = "rgba(72, 61, 34, 0.45)";
        ctx.lineWidth = Math.max(0.8, size * 0.07);

        const tipX = size * (0.88 + shape.tipScale * 0.34);
        const upperY = -size * (0.24 + shape.widthScale * 0.34) * shape.bulge;
        const lowerY = size * (0.21 + shape.widthScale * 0.39) * (2 - shape.bulge);
        const halfHeight = Math.max(Math.abs(upperY), Math.abs(lowerY));
        const gx = Math.cos(shape.gradAngle) * tipX * 0.45;
        const gy = Math.sin(shape.gradAngle) * halfHeight * 1.1;
        const fillGradient = ctx.createLinearGradient(-gx, -gy, gx, gy);
        fillGradient.addColorStop(0, colorA);
        fillGradient.addColorStop(1, colorB);
        ctx.fillStyle = fillGradient;

        // Small wave detail along edge (kept subtle near base and tip).
        const waveCount = 3 + Math.floor(rand(shape.seed + 41.1) * 3);
        const waveAmp = 0.06 + rand(shape.seed + 43.2) * 0.12;
        const upperPhase = rand(shape.seed + 47.3) * Math.PI * 2;
        const lowerPhase = rand(shape.seed + 53.4) * Math.PI * 2;

        function edgeYAt(t, isUpper) {
          // Width envelope: broader earlier, tapering more toward tip.
          const a = 0.78;
          const b = 1.38;
          const maxT = a / (a + b);
          const maxBase = Math.pow(maxT, a) * Math.pow(1 - maxT, b);
          const base = (Math.pow(t, a) * Math.pow(1 - t, b)) / maxBase;
          const target = isUpper ? upperY : lowerY;
          const fade = Math.pow(Math.sin(Math.PI * t), 1.5) * Math.pow(t, 1.25);
          const phase = isUpper ? upperPhase : lowerPhase;
          const wave = Math.sin((t * waveCount * Math.PI * 2) + phase);
          const wobble = 1 + wave * waveAmp * fade;
          return base * target * wobble;
        }

        function leafPath() {
          const steps = 26;
          ctx.beginPath();
          ctx.moveTo(0, 0);
          for (let i = 1; i <= steps; i++) {
            const t = i / steps;
            ctx.lineTo(tipX * t, edgeYAt(t, true));
          }
          for (let i = steps - 1; i >= 0; i--) {
            const t = i / steps;
            ctx.lineTo(tipX * t, edgeYAt(t, false));
          }
          ctx.closePath();
        }

        leafPath();
        ctx.fill();
        ctx.stroke();

        // Clip veins to leaf body.
        ctx.save();
        leafPath();
        ctx.clip();

        // Midrib.
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.lineTo(tipX * 0.98, 0);
        ctx.strokeStyle = "rgba(58, 48, 30, 0.48)";
        ctx.lineWidth = Math.max(0.7, size * 0.06);
        ctx.stroke();

        // Secondary veins: branch toward tip, taper from base to tip.
        const numVeins = Math.max(5, Math.round(size / 3.6));
        ctx.strokeStyle = "rgba(58, 45, 24, 0.58)";
        for (let i = 0; i < numVeins; i++) {
          // Choose the starting location (0 near base, 1 near tip)
          const startPerc = (i + 0.8) / numVeins;
          const endPerc = (i + 0.8 + Math.pow(0.7, i + 1)) / numVeins;
          const midPerc = startPerc * 0.7 + endPerc * 0.3;

          // Width narrows as we approach the tip
          ctx.lineWidth = size * 0.03 * (1 - startPerc);
          
          ctx.beginPath();
          ctx.moveTo(startPerc * tipX, 0);
          ctx.quadraticCurveTo(
            startPerc * tipX, edgeYAt(startPerc, true) * 0.5,
            endPerc * tipX, edgeYAt(endPerc, true) * 0.8
          );
          ctx.stroke();

          ctx.beginPath();
          ctx.moveTo(startPerc * tipX, 0);
          ctx.quadraticCurveTo(
            startPerc * tipX, edgeYAt(startPerc, false) * 0.5,
            endPerc * tipX, edgeYAt(endPerc, false) * 0.8
          );
          ctx.stroke();
        }

        ctx.restore();
        ctx.restore();
      }

      function drawTrunk() {
        // Main trunk body.
        for (let layer = 0; layer < 4; layer++) {
          const shade = 26 + layer * 8;
          ctx.beginPath();
          for (let i = 0; i < trunkPoints.length; i++) {
            const p = trunkPoints[i];
            const n = Math.sin(i * 0.72 + layer * 0.6) * (1.3 + layer * 0.55);
            const x = p.x + n;
            const y = p.y;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
          }
          ctx.strokeStyle = "hsl(31, 27%," + shade + "%)";
          ctx.lineWidth = trunkWidthAt(0.5) * (1.68 - layer * 0.28);
          ctx.lineCap = "round";
          ctx.lineJoin = "round";
          ctx.stroke();
        }

        // Broad side lighting to imply cylindrical bark volume.
        for (let i = 1; i < trunkPoints.length - 1; i++) {
          const p = trunkPoints[i];
          const width = trunkWidthAt(p.t);

          ctx.beginPath();
          ctx.moveTo(p.x - width * 0.34, p.y - width * 0.22);
          ctx.lineTo(p.x - width * 0.15, p.y + width * 0.24);
          ctx.strokeStyle = "rgb(145, 118, 82)";
          ctx.lineWidth = Math.max(0.55, width * 0.06);
          ctx.stroke();

          ctx.beginPath();
          ctx.moveTo(p.x + width * 0.2, p.y - width * 0.24);
          ctx.lineTo(p.x + width * 0.38, p.y + width * 0.2);
          ctx.strokeStyle = "rgb(44, 29, 16)";
          ctx.lineWidth = Math.max(0.55, width * 0.065);
          ctx.stroke();
        }

        // A few deep vertical bark seams (not many, to avoid hairiness).
        const seamOffsets = [-0.3, -0.08, 0.13, 0.31];
        for (let s = 0; s < seamOffsets.length; s++) {
          const offset = seamOffsets[s];
          ctx.beginPath();
          let started = false;
          for (let i = 1; i < trunkPoints.length - 1; i++) {
            const p = trunkPoints[i];
            const width = trunkWidthAt(p.t);
            const jitter = (rand((s + 1) * 37 + i * 0.9) - 0.5) * width * 0.04;
            const x = p.x + offset * width + jitter;
            const y = p.y;
            if (!started) {
              ctx.moveTo(x, y);
              started = true;
            } else {
              ctx.lineTo(x, y);
            }
          }
          const depth = 1 - Math.abs(offset) * 1.8;
          ctx.strokeStyle = "rgb(58, 38, 22)";
          ctx.lineWidth = Math.max(0.55, trunkWidthAt(0.5) * (0.03 + depth * 0.015));
          ctx.lineCap = "round";
          ctx.stroke();
        }

        // Occasional short cross-fissures.
        for (let i = 5; i < trunkPoints.length - 5; i += 4) {
          const p = trunkPoints[i];
          const q = trunkPoints[i + 1];
          const width = trunkWidthAt(p.t);
          const side = rand(i * 2.7) > 0.5 ? 1 : -1;
          const x0 = p.x + side * width * (0.04 + rand(i * 4.1) * 0.22);
          const y0 = p.y + (rand(i * 3.3) - 0.5) * width * 0.35;
          const len = width * (0.16 + rand(i * 5.2) * 0.2);

          ctx.beginPath();
          ctx.moveTo(x0, y0);
          ctx.lineTo(x0 + len * 0.42, q.y + (y0 - p.y) - len * 0.2);
          ctx.strokeStyle = "rgb(52, 34, 20)";
          ctx.lineWidth = Math.max(0.4, width * 0.04);
          ctx.stroke();
        }
      }

      function drawVines() {
        const turns = 9.5;
        const samples = 230;

        function drawPass(front) {
          ctx.beginPath();
          let started = false;
          for (let i = 0; i <= samples; i++) {
            const t = i / samples;
            const p = pointAt(t);
            const radius = trunkWidthAt(t) * 0.7;
            const theta = t * turns * TAU + 0.35;
            const depth = Math.cos(theta);
            const shouldDraw = front ? depth > 0 : depth <= 0;
            if (!shouldDraw) {
              started = false;
              continue;
            }
            const x = p.x + Math.sin(theta) * radius;
            const y = p.y;
            if (!started) {
              ctx.moveTo(x, y);
              started = true;
            } else {
              ctx.lineTo(x, y);
            }
          }

          ctx.strokeStyle = front
            ? "rgb(90, 68, 44)"
            : "rgb(58, 42, 27)";
          ctx.lineWidth = front ? Math.max(1.2, w * 0.0036) : Math.max(1, w * 0.0028);
          ctx.lineCap = "round";
          ctx.stroke();
        }

        drawPass(false);
        drawPass(true);
      }

      function drawLeaves() {
        const start = { r: 108, g: 109, b: 59 };  // #6c6d3b
        const end = { r: 236, g: 230, b: 194 };   // #ece6c2

        function colorAt(t) {
          const r = Math.round(lerp(start.r, end.r, t));
          const g = Math.round(lerp(start.g, end.g, t));
          const b = Math.round(lerp(start.b, end.b, t));
          return "rgb(" + r + ", " + g + ", " + b + ")";
        }

        for (let i = 0; i < leaves.length; i++) {
          const leaf = leaves[i];
          const colorA = colorAt(leaf.gradStart);
          const colorB = colorAt(leaf.gradEnd);

          // Petiole/stem: visibly connects each leaf to a twig or vine anchor.
          ctx.beginPath();
          ctx.moveTo(leaf.anchorX, leaf.anchorY);
          ctx.quadraticCurveTo(
            lerp(leaf.anchorX, leaf.baseX, 0.5),
            lerp(leaf.anchorY, leaf.baseY, 0.5) - leaf.stemWidth * 1.8,
            leaf.baseX,
            leaf.baseY
          );
          ctx.strokeStyle = "rgb(88, 72, 42)";
          ctx.lineWidth = leaf.stemWidth;
          ctx.lineCap = "round";
          ctx.stroke();

          drawLeaf(
            leaf.baseX,
            leaf.baseY,
            leaf.size,
            leaf.angle,
            leaf,
            colorA,
            colorB,
            0.9
          );
        }
      }

      function paint() {
        ctx.clearRect(0, 0, w, h);
        drawVines();
        drawTrunk();
        drawTwigs();
        drawLeaves();
      }

      window.addEventListener("resize", resize, { passive: true });
      resize();
    })();
  </script>
</body>
</html>
