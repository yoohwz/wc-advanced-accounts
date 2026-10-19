#!/usr/bin/env python3
"""Verify the event's candidate still identifies the current open PR and base."""
import json
import os
import subprocess
import urllib.request


def verify(event, current, repo, head, base):
    expected = event['pull_request']
    assert current['number'] == expected['number'], 'PR number moved'
    assert current['state'] == 'open', 'PR is not open'
    assert current['base']['repo']['full_name'] == repo, 'Wrong base repository'
    assert current['base']['ref'] == 'main', 'Wrong base branch'
    assert current['head']['repo']['full_name'] == expected['head']['repo']['full_name'], 'Head repository moved'
    assert current['head']['sha'] == expected['head']['sha'] == head, 'Head moved'
    assert current['base']['sha'] == expected['base']['sha'] == base, 'Base moved'
    assert current['draft'] == expected['draft'], 'Draft/Ready state moved'
    assert event['action'] == 'ready_for_review' or current['draft'], 'Final gate requires ready_for_review'


def main():
    with open(os.environ['GITHUB_EVENT_PATH']) as stream:
        event = json.load(stream)
    repo = os.environ['GITHUB_REPOSITORY']
    number = int(event['number'])
    request = urllib.request.Request(
        f"https://api.github.com/repos/{repo}/pulls/{number}",
        headers={'Authorization': 'Bearer ' + os.environ['GH_TOKEN'], 'Accept': 'application/vnd.github+json'})
    with urllib.request.urlopen(request, timeout=30) as response:
        current = json.load(response)
    head = subprocess.check_output(['git', 'rev-parse', 'HEAD'], text=True).strip()
    base = os.environ['AAC_BASE']
    verify(event, current, repo, head, base)
    print(f"Verified PR #{number}: head={head} base={base} event={event['action']}")


if __name__ == '__main__':
    main()
