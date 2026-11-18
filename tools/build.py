"""Build deterministic Nextcloud archives from an explicit runtime allowlist."""
from pathlib import Path
import gzip
import hashlib
import io
import re
import tarfile
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[1]
APP_ID = "upload_size_notice"
RUNTIME = {"appinfo": {".php", ".xml"}, "css": {".css"}, "img": {".svg"},
           "js": {".js"}, "lib": {".php"}, "templates": {".php"},
           "tests": {".php", ".cjs"}, "docs": {".md", ".svg"}}
DOCUMENTS = ["LICENSE", "README.md", "README.fa.md", "CHANGELOG.md", "CONTRIBUTING.md", "SECURITY.md"]


def metadata():
    info = ET.parse(ROOT / "appinfo/info.xml").getroot()
    version = info.findtext("version", "")
    if info.findtext("id") != APP_ID or not re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+", version):
        raise ValueError("Unexpected app ID or invalid release version")
    return version


def release_files():
    paths = [ROOT / name for name in DOCUMENTS]
    for folder, suffixes in RUNTIME.items():
        for path in (ROOT / folder).rglob("*"):
            if path.is_symlink():
                raise ValueError("Symlinks are not allowed in release inputs")
            if path.is_file() and path.suffix in suffixes and not any(p.startswith(".") for p in path.relative_to(ROOT).parts):
                paths.append(path)
    for path in paths:
        if not path.is_file() or path.is_symlink():
            raise ValueError("Missing or unsafe release input: " + str(path.relative_to(ROOT)))
    return sorted(set(paths))


def build():
    version = metadata()
    files = release_files()
    output = ROOT / "dist"
    output.mkdir(exist_ok=True)
    archives = [output / f"{APP_ID}-{version}.zip", output / f"{APP_ID}-{version}.tar.gz"]
    with zipfile.ZipFile(archives[0], "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for path in files:
            name = APP_ID + "/" + path.relative_to(ROOT).as_posix()
            info = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, path.read_bytes())
    with archives[1].open("wb") as raw, gzip.GzipFile(fileobj=raw, mode="wb", filename="", mtime=0) as compressed:
        with tarfile.open(fileobj=compressed, mode="w", format=tarfile.PAX_FORMAT) as archive:
            for path in files:
                data = path.read_bytes()
                info = tarfile.TarInfo(APP_ID + "/" + path.relative_to(ROOT).as_posix())
                info.size = len(data)
                info.mode = 0o644
                info.mtime = 0
                info.uid = info.gid = 0
                archive.addfile(info, io.BytesIO(data))
    checksums = [hashlib.sha256(path.read_bytes()).hexdigest() + "  " + path.name for path in archives]
    (output / "SHA256SUMS").write_text("\n".join(checksums) + "\n", encoding="ascii")
    print(f"Built {len(files)} files for {APP_ID} {version}")
    for path in archives:
        print(path.name, path.stat().st_size, "bytes")


if __name__ == "__main__":
    build()
