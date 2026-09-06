import os
import zipfile

source_dir = os.path.abspath(os.path.join("wordpress-plugin", "door-open-intro"))
output_zip = os.path.abspath(os.path.join("wordpress-plugin", "door-open-intro.zip"))

if os.path.exists(output_zip):
    os.remove(output_zip)

print(f"Packaging {source_dir} -> {output_zip} with strict forward-slash POSIX paths...")

with zipfile.ZipFile(output_zip, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as zipf:
    # First, add the root directory entry
    root_entry = zipfile.ZipInfo("door-open-intro/")
    root_entry.external_attr = 0o755 << 16
    zipf.writestr(root_entry, "")

    for root, dirs, files in os.walk(source_dir):
        # Sort for deterministic output
        dirs.sort()
        files.sort()

        rel_dir = os.path.relpath(root, source_dir)
        if rel_dir == ".":
            zip_dir = "door-open-intro"
        else:
            zip_dir = f"door-open-intro/{rel_dir.replace(os.sep, '/')}"

        # Add directory entry if not root
        if rel_dir != ".":
            dir_entry = zipfile.ZipInfo(f"{zip_dir}/")
            dir_entry.external_attr = 0o755 << 16
            zipf.writestr(dir_entry, "")

        for file in files:
            # Skip temp or hidden files
            if file.startswith("."):
                continue
            file_path = os.path.join(root, file)
            arcname = f"{zip_dir}/{file}"
            
            # Create ZipInfo to guarantee forward slash and standard permissions
            zinfo = zipfile.ZipInfo(arcname)
            zinfo.compress_type = zipfile.ZIP_DEFLATED
            zinfo.external_attr = 0o644 << 16  # Standard readable file permission
            
            # Preserve modification time
            stat = os.stat(file_path)
            zinfo.date_time = (
                zipfile.time.localtime(stat.st_mtime)[0:6]
            )

            with open(file_path, "rb") as f:
                zipf.writestr(zinfo, f.read())

print("Verifying created ZIP entries:")
with zipfile.ZipFile(output_zip, "r") as z:
    for info in z.infolist():
        has_backslash = "\\" in info.filename
        print(f" - {info.filename} (Backslash: {has_backslash}, Size: {info.file_size} bytes)")
        if has_backslash:
            raise ValueError(f"ERROR: Entry {info.filename} contains backslash!")

print("\nSUCCESS: ZIP created with 100% valid forward-slash paths for WordPress / Linux!")
