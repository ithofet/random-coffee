#include <vector>
#include <fstream>
#include <cstring>
using namespace std;

vector<vector<int>> g;
int *mt;
bool *used;

bool dfs(int v) {
    if (used[v])
        return false;
    used[v] = true;
    for (int u: g[v]) {
        if (mt[u] == -1 || dfs(mt[u])) {
            mt[u] = v;
            return true;
        }
    }
    return false;
}
/*
 * Input data format:
 * b (b % 2 = 0)
 * size(1) a(1) a(2) ... a(size)
 * ...
 * size(b)  a(1) a(2) ... a(size)
 */
int main() {
    ifstream in("input.txt");
    int numUser;
    in >> numUser;
    int numLen = numUser / 2;
    vector<int> temp;
    for (int i = 0; i < numLen; i++) {
        int size;
        in >> size;
        temp.reserve(size);
        int tmp;
        for (int j = 0; j < size; j++) {
            in >> tmp;
            temp.push_back(tmp);
        }
        g.push_back(temp);
        temp.clear();
    }
    in.close();

    mt = new int[numUser];
    used = new bool[numUser];
    int cnt = 0;
    memset(mt, -1, numUser * sizeof(int));
    for (int i = 0; i < numLen; i++) {
        memset(used, 0, numUser * sizeof(bool));
        if (dfs(i))
            cnt++;
    }
    ofstream out("output.txt");
    out << cnt << '\n';
    for (int i = 0; i < numUser; i++)
        if (mt[i] != -1)
            out << i << ' ' << mt[i] << '\n';
    out.close();
    delete[] mt;
    delete[] used;
    return 0;
}
