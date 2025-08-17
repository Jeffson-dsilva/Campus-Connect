Archived items (date: auto)

This folder contains non-runtime assets moved out of the active web app to keep the deployment footprint small.

Moved in this pass:
- final internship.zip -> archive/final internship.zip
- loginold.php -> archive/loginold.php
- project_prediction/integration -> archive/project_prediction/integration/
- assets/college_ipm_system.sql -> archive/db/college_ipm_system.sql

Restore instructions:
- To restore a file/folder, move it back to its original path.
- Example (PowerShell):
  Move-Item "archive/loginold.php" ".\loginold.php"

Notes:
- Archiving is conservative to avoid breaking the app. If something is missing at runtime, restore and let us know.
- Future candidates can be added here after review (e.g., more demo/test files).
