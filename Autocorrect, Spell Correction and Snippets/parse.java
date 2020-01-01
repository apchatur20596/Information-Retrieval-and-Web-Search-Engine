

public class parse {
	public static void main(String args[]) throws Exception {
		String op = "/Users/snehasalvi/Documents/workspace/Assignment4/parsed/";
		// PrintWriter writer = new PrintWriter (op+"trial.txt");
		String dirPath = "/Users/snehasalvi/Documents/workspace/Assignment4/src/NYCTimesDownloadData";
		File dir = new File(dirPath);

		int count = 1;

		try {

			for (File file : dir.listFiles()) {

				System.out.println(file.getName().substring(0,file.getName().length()-5));
				System.out.println(file.getName());
				PrintWriter writer = new PrintWriter(op + file.getName().substring(0,file.getName().length()-5));
				count++;

				BodyContentHandler handler = new BodyContentHandler(-1);

				Metadata metadata = new Metadata();

				ParseContext pcontext = new ParseContext();

				HtmlParser htmlparser = new HtmlParser();

				FileInputStream inputstream = new FileInputStream(file);

				htmlparser.parse(inputstream, handler, metadata, pcontext);

				String content = handler.toString().trim().replaceAll(" +", " ").replaceAll("[\r\n]+", "\n");
				//System.out.println(content);
				writer.print(content);
				System.out.println(count);
				writer.close();

				
			}

		} catch (Exception e) {

			System.err.println("Caught IOException: " + e.getMessage());

			e.printStackTrace();

		}

		

	}

}