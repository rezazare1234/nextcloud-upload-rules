"""Portable checks. No production server, credentials, or network access required."""
import argparse
from pathlib import Path
import shutil
import subprocess
import sys
from build import metadata, release_files

ROOT = Path(__file__).resolve().parents[1]


def run(*args):
    subprocess.run(args, cwd=ROOT, check=True)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--skip-php", action="store_true", help="Partial validation only; CI never skips PHP")
    args = parser.parse_args()
    metadata()
    files = release_files()
    for path in files:
        if path.suffix in {".php", ".js", ".cjs", ".xml", ".css"}:
            data = path.read_bytes()
            if data.startswith(b"\xef\xbb\xbf") and path.suffix == ".php":
                raise ValueError("PHP file contains a UTF-8 BOM: " + str(path.relative_to(ROOT)))
            data.decode("utf-8")
    node = shutil.which("node")
    if not node:
        raise RuntimeError("Install Node.js 18+ to run JavaScript checks")
    for path in sorted((ROOT / "js").glob("*.js")):
        run(node, "--check", str(path))
    run(node, "tests/policy.cjs")
    if args.skip_php:
        print("PARTIAL CHECK: PHP syntax and policy tests explicitly skipped")
    else:
        php = shutil.which("php")
        if not php:
            raise RuntimeError("Install PHP 8.2+ with mbstring; use --skip-php only for a partial local check")
        run(php, "-r", "exit(PHP_VERSION_ID >= 80200 && extension_loaded('mbstring') ? 0 : 1);")
        for path in files:
            if path.suffix == ".php":
                run(php, "-l", str(path))
        run(php, "tests/policy.php")
    print("Metadata, UTF-8 and JavaScript checks passed" if args.skip_php else "All portable checks passed")


if __name__ == "__main__":
    try:
        main()
    except (ValueError, RuntimeError, subprocess.CalledProcessError) as error:
        print(str(error), file=sys.stderr)
        raise SystemExit(1)
