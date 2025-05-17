s## GitHub Copilot Chat

- Extension Version: 0.27.0 (prod)
- VS Code: vscode/1.100.0
- OS: Windows

## Network

User Settings:
```json
  "github.copilot.advanced.debug.useElectronFetcher": true,
  "github.copilot.advanced.debug.useNodeFetcher": false,
  "github.copilot.advanced.debug.useNodeFetchFetcher": true
```

Connecting to https://api.github.com:
- DNS ipv4 Lookup: 140.82.121.6 (101 ms)
- DNS ipv6 Lookup: Error (6 ms): getaddrinfo ENOTFOUND api.github.com
- Proxy URL: None (1 ms)
- Electron fetch (configured): HTTP 200 (966 ms)
- Node.js https: HTTP 200 (161 ms)
- Node.js fetch: HTTP 200 (135 ms)
- Helix fetch: HTTP 200 (1339 ms)

Connecting to https://api.individual.githubcopilot.com/_ping:
- DNS ipv4 Lookup: 140.82.113.21 (73 ms)
- DNS ipv6 Lookup: Error (7 ms): getaddrinfo ENOTFOUND api.individual.githubcopilot.com
- Proxy URL: None (1806 ms)
- Electron fetch (configured): HTTP 200 (415 ms)
- Node.js https: HTTP 200 (414 ms)
- Node.js fetch: timed out after 10 seconds
- Helix fetch: timed out after 10 seconds

## Documentation

In corporate networks: [Troubleshooting firewall settings for GitHub Copilot](https://docs.github.com/en/copilot/troubleshooting-github-copilot/troubleshooting-firewall-settings-for-github-copilot).