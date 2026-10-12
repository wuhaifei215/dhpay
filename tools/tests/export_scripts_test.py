"""Verify export scripts using only a generated temporary project."""
from pathlib import Path
import argparse
import os
import shutil
import subprocess
import tempfile
import time

parser = argparse.ArgumentParser()
git_bash = Path('C:/Program Files/Git/bin/bash.exe')
parser.add_argument('--bash', default=str(git_bash) if git_bash.is_file() else shutil.which('bash'))
args = parser.parse_args()
if not args.bash:
    raise SystemExit('Bash is required to validate export scripts.')
project = Path(__file__).resolve().parents[2]
scripts = ['export.sh', 'del_export.sh']
for name in scripts:
    source = project / 'api/shell' / name
    assert b'\r' not in source.read_bytes(), 'Shell script must use LF: ' + name
    subprocess.run([args.bash, '-n', source.as_posix()], check=True)

with tempfile.TemporaryDirectory(prefix='dhpay2-export-shell-') as directory:
    root = Path(directory).resolve()
    assert root.parent == Path(tempfile.gettempdir()).resolve()
    shell = root / 'api/shell'
    shell.mkdir(parents=True)
    download = root / 'open/Uploads/download/20261001'
    download.mkdir(parents=True)
    runtime = root / 'open/Runtime/Temp/export_fixture'
    runtime.mkdir(parents=True)
    for name in scripts:
        shutil.copyfile(project / 'api/shell' / name, shell / name)
    (root / 'open/cli.php').write_text('<?php echo getcwd()."\\n".$argv[1]."\\n";', encoding='utf-8')
    result = subprocess.run([args.bash, (shell / 'export.sh').as_posix()], cwd=root, check=True, capture_output=True, text=True)
    output = result.stdout.strip().splitlines()
    assert Path(output[0]).resolve() == root / 'open' and output[1] == 'Export', 'Export runs in the sibling open application'
    old_time = time.time() - 8 * 86400
    old_zip = download / 'expired.zip'
    current_zip = download / 'current.zip'
    partial = download / 'unfinished.zip.partial'
    csv = download / 'keep.csv'
    note = download / 'keep.txt'
    runtime_part = runtime / 'part_00000000.csv'
    unrelated = root / 'unrelated' / 'expired.zip'
    unrelated.parent.mkdir()
    for file in [old_zip, current_zip, partial, csv, note, runtime_part, unrelated]:
        file.write_text('test fixture', encoding='utf-8')
        if file != current_zip:
            os.utime(file, (old_time, old_time))
    subprocess.run([args.bash, (shell / 'del_export.sh').as_posix()], cwd=root, check=True)
    assert not old_zip.exists(), 'Expired finished ZIP removed'
    assert all(file.exists() for file in [current_zip, partial, csv, note, runtime_part, unrelated]), 'Active, partial and unrelated files preserved'
    assert download.is_dir() and runtime.is_dir(), 'Directories preserved'
print('PASS export scripts: LF and syntax, relative launch path, ZIP retention and file boundaries')
