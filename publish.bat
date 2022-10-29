::发布
chcp 65001
@echo off
git add *
git commit -m ''
git push
::添加tag
git tag v3.5
::删除tag
::git tag -d v2.4
::查看所有tag
git tag
::上传所有tag
git push --tag

pause