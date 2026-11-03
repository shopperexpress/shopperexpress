/**
 * Converts the project's raster images to a single modern format.
 *
 *   node tools/convert-images.mjs webp|avif [--force] [--restore]
 *
 * One format per run — never both at once. Originals are moved to `_originals/`, so the
 * output can be reviewed before it is accepted; delete that folder once you are happy, or
 * pass `--restore` to put them back.
 */
import { existsSync } from 'fs';
import { mkdir, readdir, readFile, rename, rm, stat, writeFile } from 'fs/promises';
import { dirname, extname, join, relative, sep } from 'path';
import sharp from 'sharp';

const OPTIONS = {
  // https://sharp.pixelplumbing.com/api-output#webp
  webp: { quality: 80 },
  // AVIF's quality scale is not comparable to WebP's — ~50-60 lands near WebP 80.
  // https://sharp.pixelplumbing.com/api-output#avif
  avif: { quality: 55 },
};

// SVG is vector (nothing to gain) and GIF is usually animated, which AVIF handles poorly.
const SOURCE_EXTENSIONS = ['.jpg', '.jpeg', '.png'];
const ORIGINALS_DIR = '_originals';

const ROOT = process.cwd();
const args = process.argv.slice(2);
// Split, checked piecewise — `--restore` is valid without a format.
const [format] = args.filter((arg) => !arg.startsWith('--'));
const isForced = args.includes('--force');
const isRestore = args.includes('--restore');

const formatSize = (bytes) => `${(bytes / 1024).toFixed(1)} KB`;

const swapExtension = (filePath, extension) => filePath.replace(/\.[^.]+$/, `.${extension}`);

// Collects files recursively, skipping `_`-prefixed folders — the same convention
// webpack.config.mjs uses to keep partials out of the build.
const walk = async (dir, extensions) => {
  const entries = await readdir(dir, { withFileTypes: true });
  const collected = await Promise.all(
    entries.map(async (entry) => {
      const entryPath = join(dir, entry.name);

      if (entry.isDirectory()) {
        return entry.name.startsWith('_') ? [] : walk(entryPath, extensions);
      }

      return !extensions || extensions.includes(extname(entry.name).toLowerCase()) ? [entryPath] : [];
    })
  );

  return collected.flat();
};

const fail = (message) => {
  console.error(`\n${message}\n`);
  process.exit(1);
};

const getImagesDir = async () => {
  let config;

  try {
    config = JSON.parse(await readFile(join(ROOT, 'config.json'), 'utf8'));
  } catch (error) {
    fail(`Could not read ${join(ROOT, 'config.json')}: ${error.message}\nRun this from the theme directory.`);
  }

  const imagesConfig = config.static?.images ?? { src: 'images' };

  return join(ROOT, config.src ?? 'src', imagesConfig.src);
};

const IMAGES_DIR = await getImagesDir();
const ORIGINALS_PATH = join(IMAGES_DIR, ORIGINALS_DIR);

// Path of a source relative to the images root, with the `_originals/` prefix stripped —
// so `_originals/hero/banner.jpg` and `hero/banner.jpg` resolve to the same output.
const getLogicalPath = (source) => {
  const relativePath = relative(IMAGES_DIR, source);

  return relativePath.startsWith(`${ORIGINALS_DIR}${sep}`) ? relativePath.slice(ORIGINALS_DIR.length + 1) : relativePath;
};

const restoreOriginals = async () => {
  if (!existsSync(ORIGINALS_PATH)) {
    console.log(`Nothing to restore — ${relative(ROOT, ORIGINALS_PATH)} does not exist.`);
    return;
  }

  const originals = await walk(ORIGINALS_PATH);
  let restored = 0;

  for (const original of originals) {
    const target = join(IMAGES_DIR, getLogicalPath(original));

    if (existsSync(target)) {
      console.log(`! ${relative(ROOT, target)} already exists — left in ${ORIGINALS_DIR}/`);
      continue;
    }

    await mkdir(dirname(target), { recursive: true });
    await rename(original, target);
    restored += 1;
  }

  if (restored === originals.length) {
    await rm(ORIGINALS_PATH, { recursive: true, force: true });
  }

  console.log(`\nRestored ${restored} of ${originals.length} original(s). Generated images were left untouched — remove them by hand.`);
};

const convert = async () => {
  const sources = [...(await walk(IMAGES_DIR, SOURCE_EXTENSIONS)), ...(existsSync(ORIGINALS_PATH) ? await walk(ORIGINALS_PATH, SOURCE_EXTENSIONS) : [])];

  if (!sources.length) {
    console.log(`No .jpg / .jpeg / .png files found in ${relative(ROOT, IMAGES_DIR)}.`);
    return;
  }

  // Group by target first: `logo.jpg` and `logo.png` both want `logo.webp`, and converting
  // either one would clobber the other's output while moving both originals out of the build.
  const targets = new Map();

  for (const source of sources) {
    const logicalPath = getLogicalPath(source);
    const output = join(IMAGES_DIR, swapExtension(logicalPath, format));

    targets.set(output, [...(targets.get(output) ?? []), { source, logicalPath }]);
  }

  let converted = 0;
  let movedAside = false;
  let savedBytes = 0;
  const failures = [];

  for (const [output, candidates] of targets) {
    if (candidates.length > 1) {
      console.error(
        `✗ ${candidates.map(({ source }) => relative(ROOT, source)).join(' and ')} both convert to ${relative(ROOT, output)} — rename one of them, none were converted`
      );
      failures.push(relative(ROOT, output));
      continue;
    }

    const [{ source, logicalPath }] = candidates;
    const label = relative(ROOT, source);

    if (existsSync(output) && !isForced) {
      console.log(`- ${label} → skipped (${swapExtension(logicalPath, format)} already exists, use --force to re-encode)`);
      continue;
    }

    try {
      // .rotate() bakes the EXIF orientation into the pixels; webp/avif carry no EXIF.
      const buffer = await sharp(source).rotate().toFormat(format, OPTIONS[format]).toBuffer();
      const { size } = await stat(source);

      if (buffer.length >= size) {
        console.log(`- ${label} → skipped (${format} is larger than the source: ${formatSize(buffer.length)} vs ${formatSize(size)})`);
        continue;
      }

      await mkdir(dirname(output), { recursive: true });
      await writeFile(output, buffer);

      const target = join(ORIGINALS_PATH, logicalPath);

      if (source !== target) {
        await mkdir(dirname(target), { recursive: true });
        await rename(source, target);
        movedAside = true;
      }

      converted += 1;
      savedBytes += size - buffer.length;
      console.log(`✓ ${label} → ${relative(ROOT, output)}  ${formatSize(size)} → ${formatSize(buffer.length)}  (-${Math.round((1 - buffer.length / size) * 100)}%)`);
    } catch (error) {
      failures.push(label);
      console.error(`✗ ${label} → ${error.message}`);
    }
  }

  console.log(`\nConverted ${converted} of ${sources.length} image(s) to ${format}, saving ${formatSize(savedBytes)}.`);

  if (movedAside) {
    console.log(`Originals moved to ${relative(ROOT, ORIGINALS_PATH)} — review the output, then delete that folder or run with --restore to undo.`);
    console.log(`Remember to point your markup at the new .${format} files.`);
  }

  if (failures.length) {
    process.exitCode = 1;
  }
};

if (isRestore) {
  await restoreOriginals();
} else if (!Object.hasOwn(OPTIONS, format)) {
  console.error(`Expected a target format of ${Object.keys(OPTIONS).join(' or ')}, got "${format ?? ''}".`);
  console.error('Usage: node tools/convert-images.mjs webp|avif [--force] [--restore]');
  process.exitCode = 1;
} else if (!existsSync(IMAGES_DIR)) {
  console.log(`Nothing to convert — ${relative(ROOT, IMAGES_DIR)} does not exist.`);
} else {
  await convert();
}
