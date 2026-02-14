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
      let leaves = [];

      const TAU = Math.PI * 2;
      // Tweak these for branch thickness/taper without touching drawing code.
      const TRUNK_BASE_WIDTH_RATIO = 0.045; // thinner default branch
      const TRUNK_BASE_WIDTH_MIN = 14;
      const TRUNK_TAPER_PERCENT = 72; // 72 = top is 28% of base width

      function clamp(v, lo, hi) {
        return Math.max(lo, Math.min(hi, v));
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

        const leafCount = Math.round(clamp(h / 10, 55, 130));
        for (let i = 0; i < leafCount; i++) {
          const seed = i * 12.73 + 17.1;
          const t = 0.03 + rand(seed) * 0.94;
          const side = rand(seed + 9.2) > 0.5 ? 1 : -1;
          const along = pointAt(t);
          const width = trunkWidthAt(t);
          const offset = width * (0.58 + rand(seed + 21.2) * 1.5);
          const yJitter = (rand(seed + 2.9) - 0.5) * width * 0.75;
          const size = 4 + rand(seed + 4.1) * 15 + (1 - t) * 6;
          const angle = side * (0.3 + rand(seed + 7.8) * 1.05);
          const widthScale = 0.32 + rand(seed + 24.3) * 0.33;
          const tipScale = 0.78 + rand(seed + 26.4) * 0.78;
          const bulge = 0.75 + rand(seed + 28.1) * 0.75;
          const foldScale = 0.64 + rand(seed + 30.9) * 0.6;
          const isCurled = rand(seed + 33.2) > 0.66;
          const curl = isCurled
            ? side * (0.14 + rand(seed + 35.6) * 0.58)
            : side * (rand(seed + 36.5) - 0.5) * 0.1;
          leaves.push({
            seed,
            baseX: along.x + side * offset,
            baseY: along.y + yJitter,
            size,
            angle,
            widthScale,
            tipScale,
            bulge,
            foldScale,
            curl,
            hueShift: rand(seed + 11.2) * 18 - 9,
            sat: 28 + rand(seed + 13.4) * 24,
            light: 36 + rand(seed + 15.6) * 17
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

      function drawLeaf(x, y, size, angle, shape, color, alpha) {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.scale(1, 0.86 * shape.foldScale);
        ctx.transform(1, 0, shape.curl * 0.35, 1, 0, 0);
        ctx.globalAlpha = alpha;
        ctx.fillStyle = color;
        ctx.strokeStyle = "rgba(72, 61, 34, 0.45)";
        ctx.lineWidth = Math.max(0.8, size * 0.07);

        const tipX = size * (0.88 + shape.tipScale * 0.34);
        const upperY = -size * (0.24 + shape.widthScale * 0.34) * shape.bulge;
        const lowerY = size * (0.21 + shape.widthScale * 0.39) * (2 - shape.bulge);

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
        for (let layer = 0; layer < 4; layer++) {
          const shade = 27 + layer * 8;
          const alpha = 0.19 + layer * 0.12;
          ctx.beginPath();
          for (let i = 0; i < trunkPoints.length; i++) {
            const p = trunkPoints[i];
            const n = Math.sin(i * 0.82 + layer * 0.6) * (1.5 + layer * 0.65);
            const x = p.x + n;
            const y = p.y;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
          }
          ctx.strokeStyle = "hsla(33, 28%," + shade + "%," + alpha + ")";
          ctx.lineWidth = trunkWidthAt(0.5) * (1.7 - layer * 0.3);
          ctx.lineCap = "round";
          ctx.lineJoin = "round";
          ctx.stroke();
        }

        for (let i = 2; i < trunkPoints.length - 2; i += 2) {
          const p = trunkPoints[i];
          const q = trunkPoints[i + 1];
          const t = p.t;
          const width = trunkWidthAt(t);
          ctx.beginPath();
          ctx.moveTo(p.x - width * 0.33, p.y);
          ctx.lineTo(q.x + width * 0.28, q.y - width * 0.45);
          ctx.strokeStyle = "rgba(74, 52, 32, 0.19)";
          ctx.lineWidth = Math.max(0.8, width * 0.06);
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
            ? "rgba(90, 68, 44,0.86)"
            : "rgba(58, 42, 27,0.32)";
          ctx.lineWidth = front ? Math.max(1.2, w * 0.0036) : Math.max(1, w * 0.0028);
          ctx.lineCap = "round";
          ctx.stroke();
        }

        drawPass(false);
        drawPass(true);
      }

      function drawLeaves() {
        for (let i = 0; i < leaves.length; i++) {
          const leaf = leaves[i];
          const color = "hsl(" + (85 + leaf.hueShift) + ", " + leaf.sat + "%, " + leaf.light + "%)";
          drawLeaf(
            leaf.baseX,
            leaf.baseY,
            leaf.size,
            leaf.angle,
            leaf,
            color,
            0.9
          );
        }
      }

      function paint() {
        ctx.clearRect(0, 0, w, h);
        drawVines();
        drawTrunk();
        drawLeaves();
      }

      window.addEventListener("resize", resize, { passive: true });
      resize();
    })();
  </script>
</body>
</html>
