<?php
// Fetch remote branch from upstream
echo "Fetching branches from upstream...\n";
passthru('git fetch --all');
