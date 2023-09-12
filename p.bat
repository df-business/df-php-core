::发布
chcp 65001
@echo off
echo 一键发布...
set ver=1.0.1
git add *
git commit -m %ver%
git push
git tag %ver%
git push --tag
echo 发布完成
pause
exit