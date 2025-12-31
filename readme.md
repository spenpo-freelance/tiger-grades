# Tiger Grades Parent Portal

Built with [wp-build](https://github.com/spenpo/wp-build)

**Note:** The `hooks/` directory contains git hooks that should be committed to the repository. After cloning, always run `./install-hooks.sh` to install them to `.git/hooks/`.

### Disabling Hooks (Temporary)

If you need to bypass a hook temporarily, you can use git's `--no-verify` flag:

```bash
# Skip pre-commit hook
git commit --no-verify -m "your message"

# Skip pre-push hook
git push --no-verify
```

**Note:** Use this sparingly. The hooks are in place to maintain code quality and ensure proper workflow.


## 🤝 Contributing

1. Fork the repository
2. Clone your fork and run `./install-hooks.sh` to install git hooks
3. Create a feature branch
4. Add your custom code to `src/`
5. Test with `./build.sh`
6. Submit a pull request

## 📄 License

MIT License - see LICENSE file for details. 