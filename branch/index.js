    (function () {
      function createBranchScene(canvas, options) {
      if (!canvas) return null;
      options = options || {};

      const ctx = canvas.getContext("2d");
      if (!ctx) return null;
      const scale = Number.isFinite(Number(options.scale))
        ? Math.max(1, Number(options.scale))
        : 1;
      const dpr = (window.devicePixelRatio || 1) * scale;
      let sceneWidthConfig = options.sceneWidth;
      let sceneHeightConfig = options.sceneHeight;

      let w = 0;
      let h = 0;
      let generationSpan = 0;
      let trunkPoints = [];
      let branches = [];
      let twigs = [];
      let leaves = [];
      let pointerActive = false;
      let pointerX = 0;
      let pointerY = 0;
      let pointerAnimateUntil = 0;
      let pointerVX = 0;
      let pointerVY = 0;
      let pointerLastMoveMs = 0;
      let animFrame = null;
      let lastPaintTime = 0;
      let trunkLeafMaxPercent = 100;
      let drawOffsetX = 0;
      let drawOffsetY = 0;
      let drawWidth = 0;
      let drawHeight = 0;

      const TAU = Math.PI * 2;
      let sceneRotationDeg = Number(options.rotationDeg) || 0;
      const MAX_LEAF_ATTACH_DIST = 5;
      const MIN_BRANCH_TWIG_DIST = 10;
      const LEAF_HOVER_RADIUS = 70;
      const HOVER_ANIM_WINDOW_MS = 300;
      // Fixed trunk thickness (independent of scene size), configurable via options.trunkBaseWidth.
      const TRUNK_BASE_WIDTH = Number(options.trunkBaseWidth) > 0 ? Number(options.trunkBaseWidth) : 15;
      const TRUNK_TAPER_PERCENT = 72; // 72 = top is 28% of base width
      const trunkWaviness = Number.isFinite(Number(options.trunkWaviness))
        ? Math.max(0, Number(options.trunkWaviness))
        : 1;
      const branchWaviness = Number.isFinite(Number(options.branchWaviness))
        ? Math.max(0, Number(options.branchWaviness))
        : 1;
      const leafColorStart = parseRgbColor(options.leafColorStart, { r: 37, g: 65, b: 37 });
      const leafColorEnd = parseRgbColor(options.leafColorEnd, { r: 68, g: 121, b: 68 });
      let branchSpecs = normalizeBranchSpecs(options.branches);

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

      function parseRgbColor(value, fallback) {
        function norm(n) {
          return clamp(Math.round(Number(n) || 0), 0, 255);
        }
        if (Array.isArray(value) && value.length >= 3) {
          return { r: norm(value[0]), g: norm(value[1]), b: norm(value[2]) };
        }
        if (value && typeof value === "object") {
          if (
            Number.isFinite(Number(value.r)) &&
            Number.isFinite(Number(value.g)) &&
            Number.isFinite(Number(value.b))
          ) {
            return { r: norm(value.r), g: norm(value.g), b: norm(value.b) };
          }
        }
        if (typeof value === "string") {
          const s = value.trim();
          const hex = s.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
          if (hex) {
            const h = hex[1];
            if (h.length === 3) {
              return {
                r: parseInt(h[0] + h[0], 16),
                g: parseInt(h[1] + h[1], 16),
                b: parseInt(h[2] + h[2], 16)
              };
            }
            return {
              r: parseInt(h.slice(0, 2), 16),
              g: parseInt(h.slice(2, 4), 16),
              b: parseInt(h.slice(4, 6), 16)
            };
          }
          const rgb = s.match(/^rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)$/i);
          if (rgb) {
            return { r: norm(rgb[1]), g: norm(rgb[2]), b: norm(rgb[3]) };
          }
        }
        return fallback;
      }

      function paletteColorAt(t) {
        const c = paletteColorObjectAt(t);
        return "rgb(" + c.r + ", " + c.g + ", " + c.b + ")";
      }

      function paletteColorObjectAt(t) {
        const u = clamp(t, 0, 1);
        return {
          r: Math.round(lerp(leafColorStart.r, leafColorEnd.r, u)),
          g: Math.round(lerp(leafColorStart.g, leafColorEnd.g, u)),
          b: Math.round(lerp(leafColorStart.b, leafColorEnd.b, u))
        };
      }

      function darkenRgb(rgb, factor) {
        const f = clamp(factor, 0, 1);
        return {
          r: Math.round(rgb.r * f),
          g: Math.round(rgb.g * f),
          b: Math.round(rgb.b * f)
        };
      }

      function rgbaFromRgb(rgb, a) {
        return "rgba(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ", " + clamp(a, 0, 1) + ")";
      }

      function normalizeBranchSpecs(specs) {
        if (!Array.isArray(specs) || specs.length === 0) return [];
        return specs
          .map((s) => ({
            percent: clamp(Number(s.percent), 0.0, 1.0),
            direction: s.direction === "right" ? "right" : "left",
            lengthFactor: Number(s.lengthFactor) > 0 ? Number(s.lengthFactor) : 1,
            waviness: Number.isFinite(Number(s.waviness))
              ? Math.max(0, Number(s.waviness))
              : null,
            thickness: Number.isFinite(Number(s.thickness))
              ? Math.max(0.1, Number(s.thickness))
              : 1
          }))
          .filter(Boolean);
      }

      function resolveSceneDimension(input) {
        let v = input;
        if (typeof v === "function") {
          try {
            v = v();
          } catch (err) {
            v = null;
          }
        }
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : null;
      }

      function resize() {
        const scene = canvas.parentElement || canvas;
        const rect = scene.getBoundingClientRect();
        const cfgW = resolveSceneDimension(sceneWidthConfig);
        const cfgH = resolveSceneDimension(sceneHeightConfig);
        w = cfgW != null
          ? Math.max(1, Math.floor(cfgW))
          : Math.max(320, Math.floor(rect.width || 320));
        h = cfgH != null
          ? Math.max(1, Math.floor(cfgH))
          : Math.max(320, Math.floor(rect.height || 320));
        rebuild();

        drawWidth = w;
        drawHeight = h;
        drawOffsetX = 0;
        drawOffsetY = 0;

        canvas.style.width = drawWidth + "px";
        canvas.style.height = drawHeight + "px";
        canvas.width = Math.floor(drawWidth * dpr);
        canvas.height = Math.floor(drawHeight * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        paint(performance.now());
      }

      function sceneRotationRad() {
        return sceneRotationDeg * Math.PI / 180;
      }

      function cardinalRotationDeg() {
        const raw = Number(sceneRotationDeg) || 0;
        let n = ((raw % 360) + 360) % 360;
        // Snap to nearest cardinal direction (0, 90, 180, 270).
        n = Math.round(n / 90) * 90;
        if (n === 360) n = 0;
        return n;
      }

      function applySceneTransform() {
        const rot = sceneRotationRad();
        if (Math.abs(rot) < 0.0001) return;
        ctx.translate(w * 0.5, h * 0.5);
        ctx.rotate(rot);
        ctx.translate(-w * 0.5, -h * 0.5);
      }

      function screenToWorld(x, y) {
        const lx = x - drawOffsetX;
        const ly = y - drawOffsetY;
        const rot = sceneRotationRad();
        if (Math.abs(rot) < 0.0001) return { x: lx, y: ly };
        const cx = w * 0.5;
        const cy = h * 0.5;
        const dx = lx - cx;
        const dy = ly - cy;
        const c = Math.cos(-rot);
        const s = Math.sin(-rot);
        return {
          x: dx * c - dy * s + cx,
          y: dx * s + dy * c + cy
        };
      }

      function trunkWidthAt(t) {
        const base = TRUNK_BASE_WIDTH;
        const taperPct = clamp(TRUNK_TAPER_PERCENT, 0, 95) / 100;
        const top = base * (1 - taperPct);
        return base + (top - base) * t;
      }

      function rebuild() {
        trunkPoints = [];
        branches = [];
        twigs = [];
        leaves = [];

        const segments = 70;
        const cx = w * 0.5;
        // Choose span by cardinal rotation:
        // 0/180 => vertical trunk uses scene height
        // 90/270 => rotated-horizontal trunk uses scene width
        const card = cardinalRotationDeg();
        const trunkSpan = (card === 90 || card === 270) ? w : h;
        generationSpan = trunkSpan;
        const yPad = (h - trunkSpan) * 0.5;
        // Keep trunk endpoints inside the scene so round caps do not clip.
        const capInset = clamp(trunkWidthAt(0.5) * 0.9, 2, Math.max(2, trunkSpan * 0.15));
        const top = yPad + capInset;
        const bottom = yPad + trunkSpan - capInset;
        const height = bottom - top;

        let bend = 0;
        for (let i = 0; i <= segments; i++) {
          const t = i / segments;
          const y = bottom - t * height;
          bend += (Math.sin(i * 0.4) + Math.cos(i * 0.23)) * 0.006 * trunkWaviness;
          const wind = Math.sin(t * 6.2 + 0.4) * (trunkSpan * 0.008 * trunkWaviness);
          const x = cx + wind + bend * trunkSpan * 0.004;
          trunkPoints.push({ x, y, t });
        }

        // Configurable side branches.
        for (let i = 0; i < branchSpecs.length; i++) {
          const b = branchSpecs[i];
          branches.push(createBranch(b.percent, b.direction, b.lengthFactor, b.waviness, b.thickness));
        }

        const leafCount = Math.round(clamp(trunkSpan / 5, 50, 1000));
        const maxTrunkLeafT = clamp(trunkLeafMaxPercent / 100, 0, 1);
        for (let i = 0; i < leafCount; i++) {
          if (maxTrunkLeafT <= 0) break;
          const seed = i * 12.73 + 17.1;
          const t = rand(seed);
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
          const attachTwig = rand(seed + 79.8) > 0.28;
          let anchorX;
          let anchorY;
          let baseX;
          let baseY;
          let stemWidth;
          let progressRefY;

          if (attachTwig) {
            const tt = clamp(t + (rand(seed + 81.2) - 0.5) * 0.08, 0, 1);
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
              width: Math.max(0.35, width * 0.055),
              onBranch: false
            });

            anchorX = tx;
            anchorY = ty;
            progressRefY = sy;
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
            const vt = clamp(t + (rand(seed + 91.2) - 0.5) * 0.06, 0, 1);
            const p = pointAt(vt);
            const radius = trunkWidthAt(vt) * 0.7;
            const theta = vt * 9.5 * TAU + 0.35;
            const vineSide = Math.sin(theta) >= 0 ? 1 : -1;
            const petiole = Math.min(MAX_LEAF_ATTACH_DIST, trunkWidthAt(vt) * (0.14 + rand(seed + 93.1) * 0.22));

            anchorX = p.x + Math.sin(theta) * radius;
            anchorY = p.y;
            progressRefY = p.y;
            baseX = anchorX + vineSide * petiole;
            baseY = anchorY - petiole * (0.08 + rand(seed + 94.6) * 0.2);
            stemWidth = Math.max(0.24, trunkWidthAt(vt) * 0.035);
          }

          const stemDx = baseX - anchorX;
          const stemDy = baseY - anchorY;
          const stemAngle = Math.atan2(stemDy, stemDx);
          const angle = stemAngle + side * (0.32 + rand(seed + 97.2) * 0.88);

          // Use trunk attach Y projected onto trunk span to avoid a 99%->100% pop-in
          // from twig/leaf offsets that can move anchorY outside the trunk range.
          const progressPerc = clamp((progressRefY - top) / (bottom - top), 0, 1);
          if (1 - progressPerc > maxTrunkLeafT) {
            if (attachTwig) {
              twigs.pop();
            }
            continue;
          }

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
            gradAngle,
            hover: 0,
            hoverTarget: 0
          });
        }

        // Extra leaves specifically for side branches (without removing trunk leaves).
        if (branches.length > 0) {
          const BRANCH_LEAF_SPACING_PX = 12;
          for (let branchIdx = 0; branchIdx < branches.length; branchIdx++) {
            const branch = branches[branchIdx];
            const curve = (branch.curveSamples && branch.curveSamples.length > 1)
              ? branch.curveSamples
              : (branch.points || [{ x: branch.x0, y: branch.y0 }, { x: branch.x1, y: branch.y1 }]);
            let branchLen = 0;
            for (let j = 1; j < curve.length; j++) {
              branchLen += Math.hypot(curve[j].x - curve[j - 1].x, curve[j].y - curve[j - 1].y);
            }
            const branchLeafCount = Math.max(2, Math.round(branchLen / BRANCH_LEAF_SPACING_PX));
            for (let slot = 0; slot < branchLeafCount; slot++) {
              const seed = 20000 + branchIdx * 1000 + slot * 17.31;
              const bt = branchLeafCount <= 1 ? 0 : (slot / (branchLeafCount - 1));
            const p = pointOnBranch(branch, bt);
            const p2 = pointOnBranch(branch, Math.min(1, bt + 0.03));
            const width = lerp(branch.width, branch.width * 0.55, bt);
            const side = branch.side;
            const tangentAngle = Math.atan2(p2.y - p.y, p2.x - p.x);
            // Keep upper/lower placement balanced along straighter branches.
            const normalSign = (slot % 2 === 0) ? 1 : -1;
            const normalAngle = tangentAngle + normalSign * Math.PI / 2;

            const size = 3 + rand(seed + 4.1) * 11;
            const widthScale = 0.3 + rand(seed + 5.2) * 0.35;
            const tipScale = 0.78 + rand(seed + 6.3) * 0.72;
            const bulge = 0.8 + rand(seed + 7.4) * 0.9;
            const foldScale = 0.68 + rand(seed + 8.5) * 0.5;
            const curl = side * (rand(seed + 9.6) - 0.5) * 0.22;
            const gradStart = rand(seed + 10.7);
            let gradEnd = rand(seed + 11.8);
            if (Math.abs(gradEnd - gradStart) < 0.18) {
              gradEnd = clamp(gradStart + (rand(seed + 12.9) > 0.5 ? 0.22 : -0.22), 0, 1);
            }
            const gradAngle = rand(seed + 13.1) * TAU;

            const attachTwig = rand(seed + 14.2) > 0.2;
            let anchorX;
            let anchorY;
            let baseX;
            let baseY;
            let stemWidth;

            if (attachTwig) {
              const twigLen = clamp(width * (2.2 + rand(seed + 15.3) * 2.2), MIN_BRANCH_TWIG_DIST, MIN_BRANCH_TWIG_DIST * 2.2);
              // Keep twigs mostly tangent (-10deg), but fan them above/below branch
              // so leaves distribute more evenly on both sides.
              const twigAngle = tangentAngle - (10 * Math.PI / 180) + normalSign * (24 * Math.PI / 180);
              let tx = p.x + Math.cos(twigAngle) * twigLen;
              let ty = p.y + Math.sin(twigAngle) * twigLen;
              // Keep twigs extending outward from branch side.
              if ((tx - p.x) * side < 0) {
                const flip = twigAngle + Math.PI;
                tx = p.x + Math.cos(flip) * twigLen;
                ty = p.y + Math.sin(flip) * twigLen;
              }
              twigs.push({
                x1: p.x,
                y1: p.y,
                x2: tx,
                y2: ty,
                width: Math.max(0.22, width * 0.85),
                onBranch: true
              });
              anchorX = tx;
              anchorY = ty;
              const outward = twigLen * (0.28 + rand(seed + 17.5) * 0.2);
              let ox = anchorX - p.x;
              let oy = anchorY - p.y;
              const od = Math.hypot(ox, oy);
              if (od > 0.001) {
                ox /= od;
                oy /= od;
              } else {
                ox = Math.cos(normalAngle);
                oy = Math.sin(normalAngle);
              }
              baseX = anchorX + ox * outward;
              baseY = anchorY + oy * outward;
              stemWidth = Math.max(0.18, width * 0.5);
            } else {
              const petiole = Math.min(MAX_LEAF_ATTACH_DIST, width * (1.9 + rand(seed + 19.7) * 1.9));
              anchorX = p.x;
              anchorY = p.y;
              baseX = anchorX + Math.cos(normalAngle) * petiole;
              baseY = anchorY + Math.sin(normalAngle) * petiole;
              stemWidth = Math.max(0.16, width * 0.45);
            }

            const stemDx = baseX - anchorX;
            const stemDy = baseY - anchorY;
            const stemAngle = Math.atan2(stemDy, stemDx);
            // Point leaves away from the branch along the stem direction.
            // If a twig is above the branch, this naturally points the leaf upward.
            const angle = stemAngle + (rand(seed + 21.9) - 0.5) * 0.18;

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
              gradAngle,
              hover: 0,
              hoverTarget: 0
            });
          }
          }
        }
      }

      function createBranch(percent, direction, lengthFactor, waviness, thickness) {
        const p = pointAt(percent);
        const side = direction === "right" ? 1 : -1;
        const lenMul = typeof lengthFactor === "number" ? lengthFactor : 1;
        const span = generationSpan;
        const baseLen = span * (0.055 + rand(900 + percent * 100) * 0.03) * lenMul;

        // Build branch direction from local trunk tangent so it stays consistent
        // across cardinal rotations and different scene aspect ratios.
        const ta = pointAt(clamp(percent - 0.02, 0, 1));
        const tb = pointAt(clamp(percent + 0.02, 0, 1));
        let tx = tb.x - ta.x;
        let ty = tb.y - ta.y;
        const tLen = Math.hypot(tx, ty) || 1;
        tx /= tLen;
        ty /= tLen;

        // Outward normal from trunk tangent; choose side via x direction.
        let nx = -ty;
        let ny = tx;
        if (nx * side < 0) {
          nx = -nx;
          ny = -ny;
        }

        // Mostly linear outward growth with slight tangent bias.
        let dx = nx + tx * 0.22;
        let dy = ny + ty * 0.22;
        const dLen = Math.hypot(dx, dy) || 1;
        dx /= dLen;
        dy /= dLen;
        // Normalize branch reach by horizontal distance so equal lengthFactor
        // yields similar final x-reach even when trunk waviness shifts attach x.
        const cx = w * 0.5;
        const desiredX = cx + side * baseLen;
        let len = baseLen;
        if (Math.abs(dx) > 0.04) {
          const candidateLen = (desiredX - p.x) / dx;
          if (candidateLen > 0) {
            len = candidateLen;
          }
        }
        const x1 = p.x + dx * len;
        const y1 = p.y + dy * len;

        const unx = -dy;
        const uny = dx;
        const waveScale = waviness == null ? branchWaviness : waviness;
        const waveCount = 2 + Math.floor(rand(1100 + percent * 300) * 2);
        const waveAmp = len * (0.035 + rand(1200 + percent * 250) * 0.05) * waveScale;
        const phase = rand(1300 + percent * 350) * Math.PI * 2;
        const points = [];
        const steps = 6;

        for (let i = 0; i <= steps; i++) {
          const u = i / steps;
          const lx = lerp(p.x, x1, u);
          const ly = lerp(p.y, y1, u);
          const fade = Math.sin(Math.PI * u);
          const wave = Math.sin(u * waveCount * Math.PI * 2 + phase);
          const offset = wave * waveAmp * fade;
          points.push({
            x: lx + unx * offset,
            y: ly + uny * offset
          });
        }

        const curveSamples = buildBranchCurveSamples(points);
        const lengthThicknessBoost = clamp(len / 80, 0.8, 3.2);
        const thicknessScale = thickness;

        return {
          side,
          x0: p.x,
          y0: p.y,
          x1,
          y1,
          points,
          curveSamples,
          width: Math.max(0.28, trunkWidthAt(percent) * 0.22 * lengthThicknessBoost * thicknessScale)
        };
      }

      function buildBranchCurveSamples(points) {
        const out = [];
        if (!points || points.length === 0) return out;
        out.push({ x: points[0].x, y: points[0].y });
        if (points.length === 1) return out;

        if (points.length === 2) {
          for (let i = 1; i <= 20; i++) {
            const t = i / 20;
            out.push({
              x: lerp(points[0].x, points[1].x, t),
              y: lerp(points[0].y, points[1].y, t)
            });
          }
          return out;
        }

        let start = points[0];
        for (let j = 1; j < points.length - 1; j++) {
          const c = points[j];
          const n = points[j + 1];
          const end = { x: (c.x + n.x) * 0.5, y: (c.y + n.y) * 0.5 };
          for (let i = 1; i <= 8; i++) {
            const t = i / 8;
            const mt = 1 - t;
            out.push({
              x: mt * mt * start.x + 2 * mt * t * c.x + t * t * end.x,
              y: mt * mt * start.y + 2 * mt * t * c.y + t * t * end.y
            });
          }
          start = end;
        }

        const last = points[points.length - 1];
        for (let i = 1; i <= 8; i++) {
          const t = i / 8;
          out.push({
            x: lerp(start.x, last.x, t),
            y: lerp(start.y, last.y, t)
          });
        }

        return out;
      }

      function pointOnBranch(branch, t) {
        const u = clamp(t, 0, 1);
        const points = (branch.curveSamples && branch.curveSamples.length > 1)
          ? branch.curveSamples
          : (branch.points || [{ x: branch.x0, y: branch.y0 }, { x: branch.x1, y: branch.y1 }]);
        const scaled = u * (points.length - 1);
        const i = Math.floor(scaled);
        const f = scaled - i;
        const a = points[i];
        const b = points[Math.min(points.length - 1, i + 1)];
        return {
          x: lerp(a.x, b.x, f),
          y: lerp(a.y, b.y, f)
        };
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

      function trunkVisualCenterAt(t) {
        const p = pointAt(t);
        const idx = clamp(t, 0, 1) * (trunkPoints.length - 1);
        // Match vine axis to the perceived center of stacked trunk layers.
        const widthWeights = [1.68, 1.4, 1.12, 0.84];
        const sumW = widthWeights[0] + widthWeights[1] + widthWeights[2] + widthWeights[3];
        let n = 0;
        for (let layer = 0; layer < 4; layer++) {
          const layerN = Math.sin(idx * 0.72 + layer * 0.6) * (1.3 + layer * 0.55);
          n += layerN * widthWeights[layer];
        }
        return { x: p.x + n / sumW, y: p.y };
      }

      function trunkVineRadiusAt(t) {
        // Blend tapered geometry with visual trunk width used by layered strokes.
        const visualBase = trunkWidthAt(0.5);
        const tapered = trunkWidthAt(t);
        return (visualBase * 0.64 + tapered * 0.36) * 0.7;
      }

      function buildFrontVineSegments(turns, samplesPerBand) {
        const phase = 1.2;
        const twoPi = TAU;
        const tDen = Math.max(0.0001, turns * twoPi);
        const bands = [];
        const bandSamples = Math.max(8, Math.floor(samplesPerBand || 16));
        const nMax = Math.ceil(turns) + 2;

        for (let n = -2; n <= nMax; n++) {
          const thetaL = -Math.PI * 0.5 + twoPi * n;
          const thetaR = Math.PI * 0.5 + twoPi * n;
          const tL = (thetaL - phase) / tDen;
          const tR = (thetaR - phase) / tDen;
          const a = Math.max(0, tL);
          const b = Math.min(1, tR);
          if (b - a < 0.0001) continue;

          const points = [];
          for (let i = 0; i <= bandSamples; i++) {
            const t = a + (b - a) * (i / bandSamples);
            const p = trunkVisualCenterAt(t);
            const r = trunkVineRadiusAt(t);
            const theta = t * turns * TAU + phase;
            points.push({
              x: p.x + Math.sin(theta) * r,
              y: p.y,
              r
            });
          }
          if (points.length < 2) continue;

          bands.push(points);
        }

        return bands;
      }

      function vineBandCurveParams(points, endExtendPx) {
        const first = points[0];
        const last = points[points.length - 1];
        const sx = first.x - endExtendPx;
        const sy = first.y;
        const ex = last.x + endExtendPx;
        const ey = last.y;
        let cx = (sx + ex) * 0.5;
        let cy = (sy + ey) * 0.5;
        if (points.length > 2) {
          const mid = points[Math.floor(points.length / 2)];
          cx = mid.x;
          cy = mid.y;
        }
        return { sx, sy, cx, cy, ex, ey, r: Math.max(first.r || 0, last.r || 0) };
      }

      function structureBounds() {
        let minX = Infinity;
        let minY = Infinity;
        let maxX = -Infinity;
        let maxY = -Infinity;
        const rot = sceneRotationRad();
        const hasRotation = Math.abs(rot) >= 0.0001;
        const c = Math.cos(rot);
        const s = Math.sin(rot);
        const cx = w * 0.5;
        const cy = h * 0.5;
        function rotatePoint(x, y) {
          if (!hasRotation) return { x, y };
          const dx = x - cx;
          const dy = y - cy;
          return {
            x: dx * c - dy * s + cx,
            y: dx * s + dy * c + cy
          };
        }
        function add(x, y, r) {
          const p = rotatePoint(x, y);
          minX = Math.min(minX, p.x - r);
          minY = Math.min(minY, p.y - r);
          maxX = Math.max(maxX, p.x + r);
          maxY = Math.max(maxY, p.y + r);
        }

        for (let i = 0; i < trunkPoints.length; i++) {
          const p = trunkPoints[i];
          const r = trunkWidthAt(p.t) * 1.1;
          add(p.x, p.y, r);
        }

        for (let i = 0; i < branches.length; i++) {
          const b = branches[i];
          const points = (b.curveSamples && b.curveSamples.length > 1) ? b.curveSamples : (b.points || []);
          const r = Math.max(1.2, b.width * 0.8);
          for (let j = 0; j < points.length; j++) {
            add(points[j].x, points[j].y, r);
          }
        }

        for (let i = 0; i < twigs.length; i++) {
          const twig = twigs[i];
          const r = Math.max(0.8, twig.width);
          add(twig.x1, twig.y1, r);
          add(twig.x2, twig.y2, r);
        }

        for (let i = 0; i < leaves.length; i++) {
          const leaf = leaves[i];
          const size = Math.max(1, leaf.size);
          add(leaf.baseX, leaf.baseY, size * 1.35);
        }

        // Include front-vine geometry.
        const autoLoopSpacing = 90;
        const turns = Math.max(2, generationSpan / autoLoopSpacing);
        const endExtendPx = 3;
        const vineHalfWidth = Math.max(0.75, trunkWidthAt(0.5) * 0.11);
        const vineBands = buildFrontVineSegments(turns, 14);
        for (let i = 0; i < vineBands.length; i++) {
          const band = vineBands[i];
          for (let j = 0; j < band.length; j++) {
            const p = band[j];
            add(p.x, p.y, p.r + vineHalfWidth);
          }
          if (band.length > 0) {
            const c = vineBandCurveParams(band, endExtendPx);
            add(c.sx, c.sy, c.r + vineHalfWidth);
            add(c.cx, c.cy, c.r + vineHalfWidth);
            add(c.ex, c.ey, c.r + vineHalfWidth);
          }
        }

        if (!isFinite(minX)) {
          minX = 0;
          minY = 0;
          maxX = w;
          maxY = h;
        }
        return { minX, minY, maxX, maxY };
      }

      function drawTwigs() {
        for (let i = 0; i < twigs.length; i++) {
          const twig = twigs[i];
          ctx.beginPath();
          ctx.moveTo(twig.x1, twig.y1);
          ctx.lineTo(twig.x2, twig.y2);
          ctx.strokeStyle = "rgb(104, 76, 49)";
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

          if (twig.onBranch) {
            ctx.beginPath();
            ctx.arc(twig.x1, twig.y1, Math.max(0.4, twig.width * 0.5), 0, TAU);
            ctx.fillStyle = "rgb(84, 58, 35)";
            ctx.fill();
          }
        }
      }

      function drawLeaf(x, y, size, angle, shape, colorA, colorB, borderRgb, alpha) {
        ctx.save();
        ctx.translate(x, y);
        const hover = shape.hover || 0;
        const hoverSway = hover * (Math.sin(lastPaintTime * 0.012 + shape.seed * 0.37) * 0.08);
        ctx.rotate(angle + hoverSway);
        ctx.scale(1, 0.86 * shape.foldScale);
        ctx.transform(1, 0, shape.curl * 0.35, 1, 0, 0);
        ctx.globalAlpha = alpha;
        ctx.strokeStyle = rgbaFromRgb(borderRgb, 0.85);
        ctx.lineWidth = Math.max(0.8, size * 0.06);

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
        ctx.strokeStyle = rgbaFromRgb(borderRgb, 0.72);
        ctx.lineWidth = Math.max(0.7, size * 0.06);
        ctx.stroke();

        // Secondary veins: branch toward tip, taper from base to tip.
        const numVeins = Math.max(5, Math.round(size / 3.6));
        ctx.strokeStyle = rgbaFromRgb(borderRgb, 0.78);
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
          const shade = 35 + layer * 6;
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
        const seamOffsets = [-0.36, 0, 0.36];
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
          ctx.strokeStyle = "rgba(58, 22, 22, 1)";
          ctx.lineWidth = Math.max(0.8, trunkWidthAt(0.5) * (0.045 + depth * 0.02));
          ctx.lineCap = "round";
          ctx.stroke();
        }

        // Occasional short cross-fissures.
        for (let i = 7; i < trunkPoints.length - 7; i += 9) {
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
          ctx.lineWidth = Math.max(0.65, width * 0.06);
          ctx.stroke();
        }
      }

      function drawBranches() {
        for (let i = 0; i < branches.length; i++) {
          const b = branches[i];
          const points = b.points || [{ x: b.x0, y: b.y0 }, { x: b.x1, y: b.y1 }];
          if (points.length < 2) continue;

          ctx.beginPath();
          ctx.moveTo(points[0].x, points[0].y);
          for (let j = 1; j < points.length - 1; j++) {
            const c = points[j];
            const n = points[j + 1];
            const mx = (c.x + n.x) * 0.5;
            const my = (c.y + n.y) * 0.5;
            ctx.quadraticCurveTo(c.x, c.y, mx, my);
          }
          const last = points[points.length - 1];
          ctx.lineTo(last.x, last.y);
          ctx.strokeStyle = "rgb(84, 58, 35)";
          ctx.lineWidth = b.width;
          ctx.lineCap = "round";
          ctx.lineJoin = "round";
          ctx.stroke();
        }
      }

      function drawVines() {
        const autoLoopSpacing = 90;
        const turns = Math.max(2, generationSpan / autoLoopSpacing);
        const endExtendPx = 3;
        const segments = buildFrontVineSegments(turns, 18);

        function drawSegment(points) {
          if (!points || points.length < 2) return;
          const c = vineBandCurveParams(points, endExtendPx);
          ctx.beginPath();
          ctx.moveTo(c.sx, c.sy);
          ctx.quadraticCurveTo(c.cx, c.cy, c.ex, c.ey);
          ctx.stroke();
        }

        if (segments.length === 0) {
          const p = trunkVisualCenterAt(0.5);
          segments.push([
            { x: p.x - endExtendPx, y: p.y },
            { x: p.x + endExtendPx, y: p.y }
          ]);
        }

        // Keep vine tied to the same configurable leaf palette.
        const v0 = trunkVisualCenterAt(0);
        const v1 = trunkVisualCenterAt(1);
        const vineGrad = ctx.createLinearGradient(v0.x, v0.y, v1.x, v1.y);
        vineGrad.addColorStop(0, paletteColorAt(0.22));
        vineGrad.addColorStop(1, paletteColorAt(0.72));
        ctx.strokeStyle = vineGrad;
        const minWidth = Math.max(0.75, trunkWidthAt(0.5) * 0.11);
        const maxWidth = 4;
        const targetWidth = generationSpan * 0.0019;
        ctx.lineWidth = clamp(targetWidth, minWidth, maxWidth);
        ctx.lineCap = "butt";
        for (let i = 0; i < segments.length; i++) {
          drawSegment(segments[i]);
        }
      }

      function drawLeaves() {
        function colorAt(t) {
          return paletteColorAt(t);
        }

        for (let i = 0; i < leaves.length; i++) {
          const leaf = leaves[i];
          const colorA = colorAt(leaf.gradStart);
          const colorB = colorAt(leaf.gradEnd);
          const midT = (leaf.gradStart + leaf.gradEnd) * 0.5;
          const borderRgb = darkenRgb(paletteColorObjectAt(midT), 0.52);

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
            borderRgb,
            0.9 + leaf.hover * 0.1
          );
        }
      }

      function paint(now) {
        lastPaintTime = now || performance.now();
        ctx.clearRect(0, 0, drawWidth || w, drawHeight || h);
        ctx.save();
        ctx.translate(drawOffsetX, drawOffsetY);
        applySceneTransform();
        drawTrunk();
        drawVines();
        drawBranches();
        drawTwigs();
        drawLeaves();
        ctx.restore();
      }

      function updatePointer(clientX, clientY) {
        const rect = canvas.getBoundingClientRect();
        const nx = clientX - rect.left;
        const ny = clientY - rect.top;
        const world = screenToWorld(nx, ny);
        const now = performance.now();
        const dt = Math.max(1, now - pointerLastMoveMs);
        pointerVX = (world.x - pointerX) / dt;
        pointerVY = (world.y - pointerY) / dt;
        pointerLastMoveMs = now;
        pointerX = world.x;
        pointerY = world.y;
        pointerActive = true;
        pointerAnimateUntil = now + HOVER_ANIM_WINDOW_MS;
        ensureAnimation();
      }

      function animate(now) {
        const tNow = now || performance.now();
        if (pointerActive && tNow > pointerAnimateUntil) {
          pointerActive = false;
        }
        let stillAnimating = false;
        const speed = Math.hypot(pointerVX, pointerVY);
        const motionBoost = pointerActive ? clamp(speed * 22, 0.35, 1) : 0;
        for (let i = 0; i < leaves.length; i++) {
          const leaf = leaves[i];
          if (pointerActive) {
            const dx = leaf.baseX - pointerX;
            const dy = leaf.baseY - pointerY;
            const d = Math.hypot(dx, dy);
            const u = clamp(1 - d / LEAF_HOVER_RADIUS, 0, 1);
            leaf.hoverTarget = u * u * motionBoost;
          } else {
            leaf.hoverTarget = 0;
          }
          const next = leaf.hover + (leaf.hoverTarget - leaf.hover) * 0.22;
          if (Math.abs(next - leaf.hover) > 0.002 || Math.abs(leaf.hoverTarget - next) > 0.002) {
            stillAnimating = true;
          }
          leaf.hover = next;
        }
        pointerVX *= 0.82;
        pointerVY *= 0.82;
        paint(tNow);
        if (stillAnimating || pointerActive) {
          animFrame = requestAnimationFrame(animate);
        } else {
          animFrame = null;
        }
      }

      function ensureAnimation() {
        if (animFrame == null) animFrame = requestAnimationFrame(animate);
      }

      function setTrunkLeafMaxPercent(percent) {
        trunkLeafMaxPercent = clamp(Number(percent) || 0, 0, 100);
        rebuild();
        paint(performance.now());
      }

      function setRotationDeg(deg) {
        sceneRotationDeg = Number(deg) || 0;
        paint(performance.now());
      }

      function setBranches(specs) {
        branchSpecs = normalizeBranchSpecs(specs);
        rebuild();
        paint(performance.now());
      }

      function setSceneSize(width, height) {
        const wNum = Number(width);
        const hNum = Number(height);
        sceneWidthConfig = wNum > 0 ? wNum : null;
        sceneHeightConfig = hNum > 0 ? hNum : null;
        resize();
      }

      const onResize = () => resize();
      const onMouseMove = function (e) {
        updatePointer(e.clientX, e.clientY);
      };
      const onMouseLeave = function () {
        pointerActive = false;
        ensureAnimation();
      };

      window.addEventListener("resize", onResize, { passive: true });
      canvas.addEventListener("mousemove", onMouseMove);
      canvas.addEventListener("mouseleave", onMouseLeave);
      resize();
      return {
        setTrunkLeafMaxPercent,
        setRotationDeg,
        setBranches,
        setSceneSize,
        resize,
        destroy: function () {
          window.removeEventListener("resize", onResize);
          canvas.removeEventListener("mousemove", onMouseMove);
          canvas.removeEventListener("mouseleave", onMouseLeave);
          if (animFrame != null) {
            cancelAnimationFrame(animFrame);
            animFrame = null;
          }
        }
      };
    }

      window.BranchSceneLibrary = {
        mount: createBranchScene
      };
    })();
